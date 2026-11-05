<?php

declare(strict_types=1);

namespace App\Support;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = [],
    ) {
    }

    /** @param array<string, string> $headers */
    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers));
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public static function download(string $filename, string $content, string $mime = 'application/octet-stream'): self
    {
        $safeName = str_replace(["\r", "\n", '"'], '', basename($filename));

        if ($safeName === '') {
            $safeName = 'download';
        }

        return new self($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $safeName . '"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'no-store',
        ]);
    }

    public static function noContent(int $status = 204): self
    {
        return new self('', $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $this->content;
    }
}
