<?php
declare(strict_types=1);

/**
 * Front controller para el sitio público.
 *
 * - Despacha las rutas a las vistas PHP correspondientes.
 * - Inyecta variables comunes (csrf, app url, name) en las vistas.
 * - Aplica cabeceras de seguridad.
 */

require_once __DIR__ . '/src/Config/env.php';
require_once __DIR__ . '/src/Core/Session.php';
require_once __DIR__ . '/src/Core/Csrf.php';
require_once __DIR__ . '/src/Helpers/view.php';

date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Bogota'));

// Errores fuera de pantalla (producción)
ini_set('display_errors', '0');
error_reporting(E_ALL);

$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');

$routes = [
    ''                => 'home.php',
    'index'           => 'home.php',
    'home'            => 'home.php',
    'productos'       => 'productos.php',
    'producto'        => 'producto.php',
    'carrito'         => 'carrito.php',
    'checkout'        => 'checkout.php',
    'login'           => 'login.php',
    'registro'        => 'registro.php',
    'perfil'          => 'perfil.php',
    'contacto'        => 'contacto.php',
    'admin'           => 'admin/index.php',
    'admin/productos' => 'admin/productos.php',
    'admin/pedidos'   => 'admin/pedidos.php',
    'admin/categorias' => 'admin/categorias.php',
];

if (array_key_exists($path, $routes)) {
    $file = __DIR__ . '/' . $routes[$path];
    if (is_file($file)) {
        // Variables disponibles en partials
        $csrfToken = Csrf::token();
        $appUrl = (string) Env::get('APP_URL', '');
        $appName = (string) Env::get('APP_NAME', 'Tienda de Artesanías');

        // Cabeceras de cache débiles para HTML
        header('Cache-Control: public, max-age=0, must-revalidate');
        require $file;
        exit;
    }
}

http_response_code(404);
$appName = (string) Env::get('APP_NAME', 'Tienda de Artesanías');
echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404 — ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</title></head><body><h1>404 — Página no encontrada</h1></body></html>';