<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NvidiaService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('services.nvidia.api_key', '');
        $this->baseUrl = rtrim((string) config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->defaultModel = (string) config('services.nvidia.default_model', 'meta/llama-3.3-70b-instruct');
        $this->timeout = (int) config('services.nvidia.timeout', 60);
    }

    /**
     * Determine if an API key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Create the configured HTTP client.
     */
    protected function client(): PendingRequest
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('NVIDIA_API_KEY is not configured in .env');
        }

        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(3, 1000, function ($exception) {
                return $exception instanceof RequestException &&
                    in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
            });
    }

    /**
     * Send a standard chat completion request.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     */
    public function chat(array $messages, array $options = []): string
    {
        $payload = array_merge([
            'model' => $this->defaultModel,
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 1024,
            'stream' => false,
        ], $options);

        $response = $this->client()->post('/chat/completions', $payload);

        if ($response->failed()) {
            Log::error('NVIDIA API Error', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException("NVIDIA API request failed with status {$response->status()}: {$response->body()}");
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    /**
     * Stream a chat completion response chunk-by-chunk using a callback.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param callable(string $chunk): void $onChunk
     * @param array<string, mixed> $options
     */
    public function streamChat(array $messages, callable $onChunk, array $options = []): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('NVIDIA_API_KEY is not configured in .env');
        }

        $payload = array_merge([
            'model' => $this->defaultModel,
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 2048,
            'stream' => true,
        ], $options);

        $response = Http::withToken($this->apiKey)
            ->withOptions(['stream' => true])
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        $body = $response->toPsrResponse()->getBody();

        while (!$body->eof()) {
            $line = trim($body->read(1024));

            foreach (explode("\n", $line) as $subLine) {
                $subLine = trim($subLine);
                if (!str_starts_with($subLine, 'data: ')) {
                    continue;
                }

                $jsonStr = substr($subLine, 6);
                if ($jsonStr === '[DONE]') {
                    break;
                }

                $json = json_decode($jsonStr, true);
                $delta = $json['choices'][0]['delta']['content'] ?? null;
                if ($delta !== null) {
                    $onChunk($delta);
                }
            }
        }
    }
}
