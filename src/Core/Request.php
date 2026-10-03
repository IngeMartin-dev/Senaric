<?php
declare(strict_types=1);

/**
 * Encapsula la petición HTTP entrante.
 *
 * - Tipifica método, ruta, headers, body (JSON o form-data).
 * - Ofrece acceso seguro a parámetros.
 */
final class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $cookies;
    public array $files;
    public array $headers;
    public array $routeParams = [];
    public ?string $ip;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = $this->resolvePath();
        $this->query = $_GET ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->files = $_FILES ?? [];
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $this->headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $this->headers[$name] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $this->headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $contentType = $this->header('content-type', '');
            if (is_string($contentType) && str_contains($contentType, 'application/json')) {
                $decoded = json_decode($raw, true);
                $this->body = is_array($decoded) ? $decoded : [];
            } else {
                parse_str($raw, $parsed);
                $this->body = $parsed ?: [];
            }
        } else {
            $this->body = $_POST ?? [];
        }
    }

    private function resolvePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        // Si el rewrite puso el prefijo /api, lo eliminamos para que las rutas
        // definidas en api/index.php coincidan sin tener que prefijar /api.
        if (str_starts_with($path, '/api/')) {
            $path = substr($path, 4);
        } elseif ($path === '/api') {
            $path = '/';
        }
        return $path;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);
        return isset($this->headers[$key]) ? (string) $this->headers[$key] : $default;
    }

    public function input(string $key, mixed $default = null, ?callable $sanitizer = null): mixed
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        if ($sanitizer !== null && $value !== null) {
            $value = $sanitizer($value);
        }
        return $value;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept', '');
        return is_string($accept) && str_contains($accept, 'application/json');
    }
}