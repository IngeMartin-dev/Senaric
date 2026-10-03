<?php
declare(strict_types=1);

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/../Config/env.php';

/**
 * Rate limiter basado en archivo (no requiere extensiones).
 *
 * - Implementa ventana fija con contador por minuto.
 * - Persiste en storage/cache/rate-{key}.json (se renueva cada minuto).
 * - Suficiente para tráfico bajo/medio. Para producción real, considerar Redis.
 */
final class RateLimiter
{
    public static function tooManyRequests(string $key, int $maxPerMinute): bool
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/rate-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
        $now = time();
        $window = $now - ($now % 60);

        $state = ['window' => $window, 'count' => 0];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $state = array_merge($state, $decoded);
            }
        }

        if ($state['window'] !== $window) {
            $state = ['window' => $window, 'count' => 0];
        }

        if ($state['count'] >= $maxPerMinute) {
            return true;
        }

        $state['count']++;
        @file_put_contents($file, json_encode($state), LOCK_EX);
        return false;
    }

    public static function middleware(string $bucket, ?int $max = null): callable
    {
        return static function (Request $req, callable $next) use ($bucket, $max) {
            $limit = $max ?? (int) Env::get('RATE_LIMIT_DEFAULT', 120);
            $ip = $req->ip ?? 'unknown';
            if (self::tooManyRequests($bucket . ':' . $ip, $limit)) {
                Logger::warning('rate_limit_exceeded', ['bucket' => $bucket, 'ip' => $ip]);
                return Response::error('Demasiadas solicitudes. Intenta en un minuto.', 429);
            }
            return $next($req);
        };
    }
}