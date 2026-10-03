<?php
declare(strict_types=1);

/**
 * Funciones auxiliares varias (helpers globales).
 */

require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/../Config/supabase.php';
require_once __DIR__ . '/../Core/Csrf.php';

if (!function_exists('e')) {
    /**
     * Escape HTML seguro para imprimir en plantillas.
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Construye una URL completa desde la base.
     */
    function url(string $path = ''): string
    {
        $base = rtrim((string) Env::get('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * URL a un asset público.
     */
    function asset(string $path): string
    {
        return url('public/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('storage_url')) {
    /**
     * URL pública de un archivo en Supabase Storage.
     */
    function storage_url(string $bucket, string $path): string
    {
        return Supabase::storageUrl($bucket, $path);
    }
}

if (!function_exists('format_cop')) {
    /**
     * Formatea un valor numérico como precio COP.
     */
    function format_cop(float|int $value): string
    {
        return '$' . number_format((float) $value, 0, ',', '.') . ' COP';
    }
}

if (!function_exists('csrf_meta')) {
    /**
     * Devuelve la meta tag CSRF lista para incrustar en el head.
     */
    function csrf_meta(): string
    {
        return '<meta name="csrf-token" content="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('render_partial')) {
    /**
     * Renderiza un partial (header/footer) pasando variables.
     */
    function render_partial(string $name, array $vars = []): void
    {
        $file = dirname(__DIR__) . '/public/partials/' . $name . '.php';
        if (!is_file($file)) return;
        extract($vars, EXTR_SKIP);
        require $file;
    }
}