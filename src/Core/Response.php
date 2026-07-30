<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private array $headers = [];

    private function __construct(
        private readonly string $body,
        private readonly int $status,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return (new self($body, $status))->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(array $data, int $status = 200): self
    {
        $body = (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (new self($body, $status))->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return (new self('', $status))->withHeader('Location', $to);
    }

    public function withHeader(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;

        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }
        echo $this->body;
    }
}
