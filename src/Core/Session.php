<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/env.php';

/**
 * Wrapper seguro sobre $_SESSION.
 * - Configura flags de cookie hardening.
 * - Regenera id en login para evitar session fixation.
 * - Permite guardar mensajes flash.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) return;
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $name = (string) Env::get('SESSION_NAME', 'tienda_session');
        $secure = (bool) Env::get('SESSION_SECURE_COOKIE', true);
        $httpOnly = (bool) Env::get('SESSION_HTTP_ONLY', true);
        $sameSite = (string) Env::get('SESSION_SAME_SITE', 'Lax');
        $lifetime = (int) Env::get('SESSION_LIFETIME', 7200);

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        // Configuración runtime segura
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', $httpOnly ? '1' : '0');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', $sameSite);
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_start();
        self::$started = true;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        self::$started = false;
    }

    public static function flash(string $type, ?string $message = null): ?array
    {
        self::start();
        if ($message !== null) {
            $_SESSION['_flash'][$type][] = $message;
            return null;
        }
        $msgs = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $msgs;
    }
}