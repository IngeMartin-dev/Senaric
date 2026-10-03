<?php
declare(strict_types=1);

/**
 * Encapsula la respuesta HTTP.
 */
final class Response
{
    public int $status = 200;
    /** @var array<string, string> */
    public array $headers = [];
    public mixed $body = null;

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $r = new self();
        $r->status = $status;
        $r->headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);
        $r->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return $r;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $r = new self();
        $r->status = $status;
        $r->headers = ['Location' => $url];
        $r->body = '';
        return $r;
    }

    public static function html(string $html, int $status = 200): self
    {
        $r = new self();
        $r->status = $status;
        $r->headers = ['Content-Type' => 'text/html; charset=utf-8'];
        $r->body = $html;
        return $r;
    }

    public static function text(string $text, int $status = 200): self
    {
        $r = new self();
        $r->status = $status;
        $r->headers = ['Content-Type' => 'text/plain; charset=utf-8'];
        $r->body = $text;
        return $r;
    }

    public static function empty(int $status = 204): self
    {
        $r = new self();
        $r->status = $status;
        $r->body = '';
        return $r;
    }

    public static function error(string $message, int $status = 400, array $extra = []): self
    {
        return self::json(['ok' => false, 'error' => $message] + $extra, $status);
    }

    public static function ok(mixed $data = null, int $status = 200): self
    {
        return self::json(['ok' => true, 'data' => $data], $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        // Cabeceras de seguridad por defecto
        header('X-Content-Type-Options: nosniff', true);
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
        if (is_string($this->body)) {
            echo $this->body;
        }
    }
}