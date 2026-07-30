<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
        private readonly array $files,
        private readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self($method, $path, $_GET, $_POST, $_SERVER, $_FILES, $headers);
    }

    /** Именованный конструктор для тестов и внутренних нужд роутера. */
    public static function create(
        string $method,
        string $path,
        array $query = [],
        array $post = [],
        array $server = [],
        array $files = [],
        array $headers = [],
    ): self {
        return new self(strtoupper($method), $path, $query, $post, $server, $files, $headers);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function header(string $key): ?string
    {
        return $this->headers[strtolower($key)] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
