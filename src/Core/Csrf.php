<?php
declare(strict_types=1);

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/../Config/env.php';

/**
 * Protección CSRF basada en doble cookie + token en sesión.
 *
 * El token se genera una vez por sesión y se entrega al frontend como:
 *   <meta name="csrf-token" content="...">
 *   <input type="hidden" name="_csrf" value="...">
 *
 * La verificación compara token enviado (body o header X-CSRF-Token)
 * contra el almacenado en sesión con hash_equals (timing-safe).
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();
        $token = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }
        return $token;
    }

    public static function verify(?string $token): bool
    {
        Session::start();
        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($stored) || !is_string($token)) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Middleware: rechazar la petición si el token CSRF no es válido.
     */
    public static function middleware(Request $req, callable $next): Response
    {
        $method = $req->method;
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($req);
        }

        $token = $req->input('_csrf') ?? $req->header('x-csrf-token');
        if (!is_string($token) || !self::verify($token)) {
            return Response::error('Token CSRF inválido o ausente.', 419);
        }
        return $next($req);
    }
}