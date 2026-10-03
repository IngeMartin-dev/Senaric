<?php
declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP (desarrollo local).
 *
 * Uso:
 *   php -S localhost:8000 router.php
 *
 * Replica lo que hace .htaccess en Apache:
 *   - Sirve archivos estáticos reales (css, js, img…).
 *   - Bloquea carpetas/archivos sensibles (.env, .git, src, storage).
 *   - /api/*  -> api/index.php
 *   - Resto de rutas inexistentes -> index.php
 */

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri  = rawurldecode($uri);
$file = __DIR__ . $uri;

// Carpetas y archivos sensibles
if (preg_match('#^/(\.env|\.git|src|storage|database)(/|$|\.)#i', $uri)) {
    http_response_code(404);
    echo '404';
    return true;
}

// Archivo real (assets o páginas .php): lo sirve el servidor tal cual
if ($uri !== '/' && is_file($file)) {
    return false;
}

// API
if (preg_match('#^/api(/|$)#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return true;
}

// Front controller del sitio
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
return true;