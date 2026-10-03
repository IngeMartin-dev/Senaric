<?php
declare(strict_types=1);

/**
 * Validador declarativo de entradas.
 *
 * Uso:
 *   $errors = Validator::check($_POST, [
 *       'nombre' => 'required|string|min:2|max:120',
 *       'email'  => 'required|email',
 *       'edad'   => 'integer|min:18',
 *   ]);
 */
final class Validator
{
    /** @var array<string, callable> */
    private static array $rules = [];

    public static function check(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $ruleString);

            foreach ($rulesList as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $name] = explode(':', $rule, 2);
                    $param = $name;
                }

                $err = self::apply($field, $value, $rule, $param);
                if ($err !== null) {
                    $errors[$field][] = $err;
                    break; // un error por campo es suficiente
                }
            }
        }
        return $errors;
    }

    public static function fails(array $errors): bool
    {
        return !empty($errors);
    }

    private static function apply(string $field, mixed $value, string $rule, ?string $param): ?string
    {
        // required
        if ($rule === 'required') {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                return "El campo {$field} es obligatorio.";
            }
            return null;
        }

        // Si el valor es vacío y la regla no es required, no validamos más.
        if ($value === null || $value === '') {
            return null;
        }

        return match ($rule) {
            'string' => is_string($value) ? null : "{$field} debe ser texto.",
            'integer', 'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "{$field} debe ser entero.",
            'numeric' => is_numeric($value) ? null : "{$field} debe ser numérico.",
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? null : "{$field} debe ser numérico.",
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "{$field} no es un correo válido.",
            'url' => filter_var($value, FILTER_VALIDATE_URL) ? null : "{$field} no es una URL válida.",
            'slug' => preg_match('/^[a-z0-9-]+$/', (string) $value) ? null : "{$field} solo permite letras minúsculas, números y guiones.",
            'min' => self::checkMin($value, $param) ?? null,
            'in' => self::checkIn($value, $param),
            'same' => null, // se maneja en regla personalizada
            default => null,
        };
    }

    private static function checkMin(mixed $value, ?string $param): ?string
    {
        if ($param === null) return null;
        if (is_string($value) && mb_strlen($value) < (int) $param) {
            return "Debe tener al menos {$param} caracteres.";
        }
        if ((is_int($value) || is_numeric($value)) && (float) $value < (float) $param) {
            return "Debe ser mayor o igual a {$param}.";
        }
        return null;
    }

    private static function checkIn(mixed $value, ?string $param): ?string
    {
        if ($param === null) return null;
        $allowed = explode(',', $param);
        if (!in_array((string) $value, $allowed, true)) {
            return "Valor no permitido.";
        }
        return null;
    }
}