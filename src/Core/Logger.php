<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/env.php';

/**
 * Logger simple basado en archivos.
 *
 * - Niveles: debug, info, warning, error.
 * - Escribe en storage/logs/app-YYYY-MM-DD.log
 * - Enmascara campos sensibles antes de volcar a disco.
 */
final class Logger
{
    private static array $levels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
    ];

    public static function debug(string $msg, array $ctx = []): void { self::log('debug', $msg, $ctx); }
    public static function info(string $msg, array $ctx = []): void { self::log('info', $msg, $ctx); }
    public static function warning(string $msg, array $ctx = []): void { self::log('warning', $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void { self::log('error', $msg, $ctx); }

    private static function log(string $level, string $msg, array $ctx): void
    {
        $configured = strtolower((string) Env::get('LOG_LEVEL', 'info'));
        if (self::$levels[$level] < (self::$levels[$configured] ?? 1)) {
            return;
        }

        $ctx = self::mask($ctx);
        $line = sprintf(
            "[%s] [%s] %s %s\n",
            date('c'),
            strtoupper($level),
            $msg,
            $ctx ? json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        $dir = dirname(__DIR__, 2) . '/' . trim((string) Env::get('LOG_PATH', 'storage/logs'), '/');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function mask(array $ctx): array
    {
        $sensitive = ['password', 'pwd', 'token', 'authorization', 'cookie', 'set-cookie', 'secret', 'api_key', 'apikey', 'service_role'];
        foreach ($sensitive as $key) {
            if (isset($ctx[$key])) {
                $ctx[$key] = '***';
            }
        }
        return $ctx;
    }
}