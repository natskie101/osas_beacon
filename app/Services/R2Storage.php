<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Utils;
use RuntimeException;

/**
 * Cloudflare R2 object storage over the S3 API.
 *
 * Uploads are streamed straight from disk (never held in PHP memory) and
 * reads are served by handing the caller a presigned URL, so the app never
 * proxies a byte of video — the browser talks to R2 directly, which is what
 * makes Range requests and seeking work.
 *
 * Signing is AWS Signature Version 4 with R2's conventions:
 *   - region "auto", service "s3"
 *   - path-style addressing: https://<account>.r2.cloudflarestorage.com/<bucket>/<key>
 *   - header-authenticated writes, presigned query GETs (UNSIGNED-PAYLOAD)
 */
final class R2Storage
{
    private const ALGORITHM = 'AWS4-HMAC-SHA256';
    private const REGION = 'auto';
    private const SERVICE = 's3';

    /** Long enough to finish watching a module in one sitting. */
    private const PRESIGN_TTL = 86400;

    /** Transient network failures are retried; a 4xx is not worth retrying. */
    private const PUT_ATTEMPTS = 3;

    private const RETRY_BACKOFF_US = 500000;

    /** The bucket object key is stored in files.stored_name after this prefix. */
    public const PREFIX = 'r2:';

    public static function configured(): bool
    {
        return self::accountId() !== ''
            && self::bucket() !== ''
            && self::accessKeyId() !== ''
            && self::secretAccessKey() !== '';
    }

    /**
     * Streams a local file into the bucket under $key.
     *
     * @throws RuntimeException when R2 is not configured or rejects the object.
     */
    public static function put(string $key, string $localPath, string $mime): void
    {
        self::assertConfigured();

        $payloadHash = hash_file('sha256', $localPath);

        if ($payloadHash === false) {
            throw new RuntimeException('The file could not be read for upload.');
        }

        [$endpoint, $host] = self::endpoint();
        $timestamp = gmdate('Ymd\THis\Z');
        $signedHeaders = ['content-type', 'host', 'x-amz-content-sha256', 'x-amz-date'];
        $headerValues = [
            'content-type' => $mime,
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $timestamp,
        ];

        $authorization = self::authorization(
            self::canonicalRequest('PUT', self::canonicalUri($key), '', $signedHeaders, $headerValues, $payloadHash),
            $timestamp,
            $signedHeaders,
            self::accessKeyId(),
            self::secretAccessKey(),
        );

        $url = $endpoint . self::canonicalUri($key);
        $lastError = '';

        // A fresh stream per attempt: Guzzle consumes the body, so a retry
        // cannot reuse it. 4xx responses (bad signature, denied permission) are
        // not retried because they are deterministic.
        for ($attempt = 1; $attempt <= self::PUT_ATTEMPTS; $attempt++) {
            $response = null;
            $stream = fopen($localPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('The file could not be read for upload.');
            }

            try {
                $response = self::client()->request('PUT', $url, [
                    'headers' => [
                        'Authorization' => $authorization,
                        'Content-Type' => $mime,
                        'x-amz-content-sha256' => $payloadHash,
                        'x-amz-date' => $timestamp,
                    ],
                    'body' => Utils::streamFor($stream),
                ]);
            } catch (TransferException $exception) {
                // ConnectException / RequestException extend RuntimeException, so
                // without this they would surface as if R2 had rejected the
                // object. Retry them like the network blips they usually are.
                $lastError = 'R2 could not be reached: ' . $exception->getMessage();
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($response !== null) {
                $status = $response->getStatusCode();

                if ($status >= 200 && $status < 300) {
                    return;
                }

                $lastError = sprintf(
                    'R2 rejected the upload (HTTP %d): %s',
                    $status,
                    mb_substr((string) $response->getBody(), 0, 300),
                );

                if ($status < 500) {
                    break;
                }
            }

            if ($attempt < self::PUT_ATTEMPTS) {
                usleep(self::RETRY_BACKOFF_US * $attempt);
            }
        }

        throw new RuntimeException($lastError !== '' ? $lastError : 'The upload to R2 failed.');
    }

    /**
     * A time-limited, signed URL that streams the object straight from R2.
     */
    public static function presignedGet(string $key): string
    {
        self::assertConfigured();

        [$endpoint, $host] = self::endpoint();
        $timestamp = gmdate('Ymd\THis\Z');
        $scope = self::credentialScope(substr($timestamp, 0, 8));
        $signedHeaders = ['host'];

        $query = self::queryString([
            'X-Amz-Algorithm' => self::ALGORITHM,
            'X-Amz-Credential' => self::accessKeyId() . '/' . $scope,
            'X-Amz-Date' => $timestamp,
            'X-Amz-Expires' => (string) self::PRESIGN_TTL,
            'X-Amz-SignedHeaders' => 'host',
        ]);

        $canonicalRequest = self::canonicalRequest(
            'GET',
            self::canonicalUri($key),
            $query,
            $signedHeaders,
            ['host' => $host],
            'UNSIGNED-PAYLOAD',
        );

        $signature = self::signature($canonicalRequest, $timestamp, $scope, self::secretAccessKey());

        return $endpoint . self::canonicalUri($key) . '?' . $query . '&X-Amz-Signature=' . $signature;
    }

    /**
     * Removes the object. Missing objects are treated as already gone so a
     * delete that races with a previous one still completes.
     *
     * @throws RuntimeException when R2 is reachable but refuses the request.
     */
    public static function delete(string $key): void
    {
        if (!self::configured()) {
            return;
        }

        [$endpoint, $host] = self::endpoint();
        $timestamp = gmdate('Ymd\THis\Z');
        $signedHeaders = ['host', 'x-amz-content-sha256', 'x-amz-date'];
        $headerValues = [
            'host' => $host,
            'x-amz-content-sha256' => hash('sha256', ''),
            'x-amz-date' => $timestamp,
        ];

        $authorization = self::authorization(
            self::canonicalRequest('DELETE', self::canonicalUri($key), '', $signedHeaders, $headerValues, $headerValues['x-amz-content-sha256']),
            $timestamp,
            $signedHeaders,
            self::accessKeyId(),
            self::secretAccessKey(),
        );

        $response = self::client()->request('DELETE', $endpoint . self::canonicalUri($key), [
            'headers' => [
                'Authorization' => $authorization,
                'x-amz-content-sha256' => $headerValues['x-amz-content-sha256'],
                'x-amz-date' => $timestamp,
            ],
        ]);

        if ($response->getStatusCode() === 404) {
            return;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new RuntimeException(sprintf(
                'R2 refused to delete the object (HTTP %d): %s',
                $response->getStatusCode(),
                mb_substr((string) $response->getBody(), 0, 300),
            ));
        }
    }

    // -------------------------------------------------------------------------
    // Credentials / endpoint
    // -------------------------------------------------------------------------

    private static function accountId(): string
    {
        return trim((string) config('services.r2.account_id'));
    }

    private static function bucket(): string
    {
        return trim((string) config('services.r2.bucket'));
    }

    private static function accessKeyId(): string
    {
        return trim((string) config('services.r2.access_key_id'));
    }

    private static function secretAccessKey(): string
    {
        return (string) config('services.r2.secret_access_key');
    }

    /** @return array{0: string, 1: string} [base endpoint, host] */
    private static function endpoint(): array
    {
        $host = self::accountId() . '.r2.cloudflarestorage.com';

        return ['https://' . $host, $host];
    }

    private static function assertConfigured(): void
    {
        if (!self::configured()) {
            throw new RuntimeException('Cloudflare R2 storage is not configured.');
        }
    }

    private static function client(): Client
    {
        return new Client([
            'connect_timeout' => 15,
            'timeout' => 300,
            'http_errors' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // AWS Signature Version 4 (pure — exercised offline by the signing probe)
    // -------------------------------------------------------------------------

    private static function credentialScope(string $date, string $region = self::REGION, string $service = self::SERVICE): string
    {
        return $date . '/' . $region . '/' . $service . '/aws4_request';
    }

    /**
     * Path-style URI with every segment RFC 3986 encoded (slashes preserved).
     */
    private static function canonicalUri(string $key): string
    {
        $segments = array_map(
            'rawurlencode',
            explode('/', ltrim(str_replace('\\', '/', $key), '/')),
        );

        return '/' . self::bucket() . '/' . implode('/', $segments);
    }

    /**
     * Query string sorted by name, RFC 3986 encoded — the order the canonical
     * request needs, which is also the order the URL must use.
     *
     * @param array<string, string> $parameters
     */
    private static function queryString(array $parameters): string
    {
        ksort($parameters);

        $pairs = [];
        foreach ($parameters as $name => $value) {
            $pairs[] = rawurlencode($name) . '=' . rawurlencode($value);
        }

        return implode('&', $pairs);
    }

    /**
     * @param list<string> $signedHeaders lower-case names, ascending
     * @param array<string, string> $headerValues lower-case name => value
     */
    private static function canonicalRequest(
        string $method,
        string $path,
        string $query,
        array $signedHeaders,
        array $headerValues,
        string $payloadHash,
    ): string {
        $canonicalHeaders = '';

        foreach ($signedHeaders as $name) {
            $canonicalHeaders .= $name . ':' . trim($headerValues[$name] ?? '') . "\n";
        }

        return $method . "\n"
            . $path . "\n"
            . $query . "\n"
            . $canonicalHeaders . "\n"
            . implode(';', $signedHeaders) . "\n"
            . $payloadHash;
    }

    private static function stringToSign(string $canonicalRequest, string $timestamp, string $scope): string
    {
        return self::ALGORITHM . "\n"
            . $timestamp . "\n"
            . $scope . "\n"
            . hash('sha256', $canonicalRequest);
    }

    private static function signingKey(string $date, string $secret, string $region = self::REGION, string $service = self::SERVICE): string
    {
        $key = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
        $key = hash_hmac('sha256', $region, $key, true);
        $key = hash_hmac('sha256', $service, $key, true);

        return hash_hmac('sha256', 'aws4_request', $key, true);
    }

    private static function signature(
        string $canonicalRequest,
        string $timestamp,
        string $scope,
        string $secret,
        string $region = self::REGION,
        string $service = self::SERVICE,
    ): string {
        return hash_hmac(
            'sha256',
            self::stringToSign($canonicalRequest, $timestamp, $scope),
            self::signingKey(substr($timestamp, 0, 8), $secret, $region, $service),
        );
    }

    /**
     * @param list<string> $signedHeaders
     */
    private static function authorization(
        string $canonicalRequest,
        string $timestamp,
        array $signedHeaders,
        string $accessKeyId,
        string $secret,
        string $region = self::REGION,
        string $service = self::SERVICE,
    ): string {
        $scope = self::credentialScope(substr($timestamp, 0, 8), $region, $service);

        return sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            self::ALGORITHM,
            $accessKeyId,
            $scope,
            implode(';', $signedHeaders),
            self::signature($canonicalRequest, $timestamp, $scope, $secret, $region, $service),
        );
    }
}
