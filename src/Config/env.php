<?php
declare(strict_types=1);

/**
 * Cargador de variables de entorno desde el archivo .env.
 *
 * - Lee .env una sola vez.
 * - Convierte "true"/"false"/"null"/números a sus tipos reales.
 * - Permite acceder con env('CLAVE', 'default').
 * - Falla de forma segura si APP_KEY no está definido en producción.
 */

final class Env
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $path ?? dirname(__DIR__, 2) . '/.env';

        if (!is_readable($path)) {
            // No es un error fatal: el operador puede usar variables del sistema.
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/i', $line, $m)) {
                continue;
            }

            $key = $m[1];
            $value = $m[2];

            // Quitar comillas envolventes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            self::$cache[$key] = self::cast($value);

            // Exportar también a getenv/$_SERVER para herramientas externas
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $env = getenv($key);
        if ($env !== false) {
            return self::cast($env);
        }

        return $default;
    }

    private static function cast(string $value): mixed
    {
        $lower = strtolower($value);
        return match (true) {
            $lower === 'true' => true,
            $lower === 'false' => false,
            $lower === 'null' => null,
            $lower === '' => '',
            ctype_digit($value) => (int) $value,
            is_numeric($value) && str_contains($value, '.') => (float) $value,
            default => $value,
        };
    }
}

/**
 * Helper global para acceder a variables de entorno.
 */
function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}