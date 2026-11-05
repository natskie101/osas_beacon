<?php

declare(strict_types=1);

namespace App\Support;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $request,
        public readonly array $files,
        public readonly array $server,
        public readonly array $headers,
        public readonly string $ip,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method === 'POST') {
            $spoofed = strtoupper(trim((string) ($_POST['_method'] ?? '')));

            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        $base = self::scriptBasePath();
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $pathString = rawurldecode($uri);
        $path = '/' . trim($pathString, '/');

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr((string) $key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $headers,
            (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
        );
    }

    /**
     * Resolve the application base path without trusting request-like values.
     *
     * PHP's built-in server sometimes puts the requested URI in SCRIPT_NAME
     * (notably for .html legacy URLs). Only a PHP script can be the front
     * controller, so non-PHP values must not become the base path.
     */
    public static function scriptBasePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        if ($script === '' || !str_ends_with(strtolower($script), '.php')) {
            return '';
        }

        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

        return $base === '.' || $base === '/' ? '' : $base;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->request)) {
            return $this->request[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_string($value) ? trim($value) : $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        if (!$this->has($key)) {
            return $default;
        }

        return filter_var($this->input($key), FILTER_VALIDATE_BOOL);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->request) || array_key_exists($key, $this->query);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }

        return $result;
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function previousUrl(): string
    {
        $referer = $this->header('referer');

        if ($referer === null || $referer === '') {
            return '/';
        }

        $parts = parse_url($referer);

        if ($parts === false || !isset($parts['path'])) {
            return '/';
        }

        if (isset($parts['host'])) {
            $host = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
            $currentHost = (string) ($this->server['HTTP_HOST'] ?? '');

            if ($host !== $currentHost) {
                return '/';
            }
        }

        $url = $parts['path'];

        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        return $url;
    }
}
