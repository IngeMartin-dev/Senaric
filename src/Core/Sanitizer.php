<?php
declare(strict_types=1);

/**
 * Sanitizadores de propósito específico.
 *
 * Reglas:
 *   - Nunca confiar en datos del cliente.
 *   - Cada helper aplica una transformación concreta y predecible.
 *   - Para salida HTML usar htmlspecialchars().
 */
final class Sanitizer
{
    public static function string(mixed $value, int $maxLength = 255): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $value = (string) $value;
        $value = trim($value);
        // Eliminar caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }
        return $value;
    }

    public static function email(mixed $value): string
    {
        $value = self::string($value, 254);
        return filter_var($value, FILTER_SANITIZE_EMAIL) ?: '';
    }

    public static function int(mixed $value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $value = is_numeric($value) ? (int) $value : 0;
        return max($min, min($max, $value));
    }

    public static function float(mixed $value, float $min = 0.0, float $max = 1e12): float
    {
        $value = is_numeric($value) ? (float) $value : 0.0;
        return max($min, min($max, $value));
    }

    public static function slug(mixed $value): string
    {
        $value = self::string($value, 200);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    /**
     * Devuelve array de enteros a partir de un valor (cadena separada por comas
     * o array).
     *
     * @return int[]
     */
    public static function intList(mixed $value): array
    {
        if (is_array($value)) {
            $values = $value;
        } else {
            $values = explode(',', (string) $value);
        }
        $out = [];
        foreach ($values as $v) {
            $v = trim((string) $v);
            if (ctype_digit($v)) {
                $out[] = (int) $v;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Comprueba que una URL sea http(s).
     */
    public static function url(mixed $value): string
    {
        $value = self::string($value, 2000);
        if ($value === '') return '';
        if (filter_var($value, FILTER_VALIDATE_URL) === false) return '';
        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) return '';
        return $value;
    }

    /**
     * Sanitiza HTML permitiendo un subconjunto seguro (para descripciones
     * largas de productos).
     *
     * Implementación simple y conservadora: si se quiere HTML rico
     * es mejor almacenarlo como texto plano o usar Markdown.
     */
    public static function richText(mixed $value): string
    {
        $value = self::string($value, 5000);
        // Eliminar etiquetas salvo las permitidas
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><blockquote><a>';
        $value = strip_tags($value, $allowed);
        // Neutralizar javascript: en href
        $value = preg_replace('/(href\s*=\s*")\s*javascript:[^"]*(")/i', '$1#$2', $value) ?? $value;
        return $value;
    }
}