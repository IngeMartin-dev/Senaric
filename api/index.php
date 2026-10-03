<?php
declare(strict_types=1);

/**
 * Front controller de la API.
 * Despacha todas las rutas /api/* a sus controllers.
 */

require_once dirname(__DIR__) . '/src/Config/env.php';
require_once dirname(__DIR__) . '/src/Core/Request.php';
require_once dirname(__DIR__) . '/src/Core/Response.php';
require_once dirname(__DIR__) . '/src/Core/Router.php';
require_once dirname(__DIR__) . '/src/Core/Session.php';
require_once dirname(__DIR__) . '/src/Core/Csrf.php';
require_once dirname(__DIR__) . '/src/Core/RateLimiter.php';
require_once dirname(__DIR__) . '/src/Core/Logger.php';
require_once dirname(__DIR__) . '/src/Core/Auth.php';
require_once dirname(__DIR__) . '/src/Core/HttpExceptionHandler.php';
require_once dirname(__DIR__) . '/src/Services/CartService.php';
require_once dirname(__DIR__) . '/src/Services/ProductService.php';
require_once dirname(__DIR__) . '/src/Services/OrderService.php';
require_once dirname(__DIR__) . '/src/Services/ContactService.php';
require_once dirname(__DIR__) . '/src/Services/MailService.php';
require_once dirname(__DIR__) . '/src/Repositories/ProductRepository.php';
require_once dirname(__DIR__) . '/src/Repositories/CategoryRepository.php';
require_once dirname(__DIR__) . '/src/Repositories/OrderRepository.php';

date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Bogota'));

// Errores -> log + respuesta JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_exception_handler(static function (\Throwable $e) {
    HttpExceptionHandler::handle($e)->send();
});

$req = new Request();
$router = new Router();

// Middleware global: iniciar sesión, generar token CSRF, JSON por defecto
$router->middleware(function (Request $req, callable $next) {
    Session::start();
    if (!Session::get('_csrf_token')) {
        Csrf::token();
    }
    return $next($req);
});

/* ------------------------------ Productos ------------------------------ */
$router->get('/products', function (Request $req) {
    $service = new ProductService();
    $page = (int) ($req->query['page'] ?? 1);
    $perPage = (int) ($req->query['per_page'] ?? 12);
    $result = $service->list([
        'category' => $req->query['category'] ?? null,
        'search' => $req->query['search'] ?? null,
        'featured' => isset($req->query['featured']) ? $req->query['featured'] : null,
    ], $page, $perPage);
    return Response::ok($result);
});

$router->get('/products/{slug}', function (Request $req) {
    $service = new ProductService();
    $product = $service->show((string) ($req->routeParams['slug'] ?? ''));
    return Response::ok($product);
});

/* ----------------------------- Categorías ------------------------------ */
$router->get('/categories', function () {
    $repo = new CategoryRepository();
    return Response::ok($repo->all());
});

/* ------------------------------- Carrito ------------------------------- */
$router->get('/cart', function () {
    $items = CartService::hydrate();
    $totals = CartService::totals();
    return Response::ok(['items' => $items, 'totals' => $totals]);
});

$router->post('/cart/add', function (Request $req) {
    $pid = Sanitizer::int($req->input('product_id'), 1);
    $qty = Sanitizer::int($req->input('quantity', 1), 1, 99);
    $totals = CartService::add($pid, $qty);
    return Response::ok($totals);
}, [[Csrf::class, 'middleware']]);

$router->put('/cart/update', function (Request $req) {
    $pid = Sanitizer::int($req->input('product_id'), 1);
    $qty = Sanitizer::int($req->input('quantity'), 0, 99);
    $totals = CartService::update($pid, $qty);
    return Response::ok($totals);
}, [[Csrf::class, 'middleware']]);

$router->delete('/cart/remove', function (Request $req) {
    $pid = Sanitizer::int($req->query['product_id'] ?? null);
    if ($pid < 1) return Response::error('product_id requerido', 400);
    return Response::ok(CartService::remove($pid));
}, [[Csrf::class, 'middleware']]);

$router->delete('/cart/clear', function () {
    CartService::clear();
    return Response::ok(['items' => [], 'totals' => CartService::totals()]);
}, [[Csrf::class, 'middleware']]);

/* ----------------------------- Checkout -------------------------------- */
$router->post('/checkout', function (Request $req) {
    $user = Auth::requireUser($req);
    $service = new OrderService();
    $data = $req->body;
    $order = $service->checkout($user, $data);
    return Response::ok($order);
}, [[Csrf::class, 'middleware']]);

/* -------------------------------- Auth --------------------------------- */
$router->post('/auth/login', function (Request $req) {
    $email = Sanitizer::email($req->input('email'));
    $password = (string) $req->input('password', '');
    if ($email === '' || $password === '') {
        return Response::error('Correo y contraseña son obligatorios.', 422);
    }
    try {
        $tokens = Auth::loginWithPassword($email, $password);
        return Response::ok($tokens);
    } catch (\Throwable $e) {
        return Response::error($e->getMessage(), 401);
    }
}, [[Csrf::class, 'middleware']]);

$router->post('/auth/register', function (Request $req) {
    $email = Sanitizer::email($req->input('email'));
    $password = (string) $req->input('password', '');
    $name = Sanitizer::string($req->input('name', ''), 120);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        return Response::error('Correo inválido o contraseña muy corta (mínimo 8).', 422);
    }
    try {
        $res = Auth::registerWithPassword($email, $password, ['name' => $name]);
        return Response::ok($res);
    } catch (\Throwable $e) {
        return Response::error($e->getMessage(), 400);
    }
}, [[Csrf::class, 'middleware']]);

$router->post('/auth/logout', function () {
    Auth::logout();
    return Response::ok(['logged_out' => true]);
}, [[Csrf::class, 'middleware']]);

$router->get('/auth/me', function (Request $req) {
    $user = Auth::user($req);
    if (!$user) return Response::error('No autenticado', 401);
    unset($user['token']);
    return Response::ok($user);
});

/* ------------------------------- Pedidos ------------------------------- */
$router->get('/orders/mine', function (Request $req) {
    $user = Auth::requireUser($req);
    $repo = new OrderRepository();
    return Response::ok(['items' => $repo->listByUser($user['id'])]);
});

/* ------------------------------- Contacto ------------------------------ */
$router->post('/contact', function (Request $req) {
    $service = new ContactService();
    try {
        $r = $service->submit($req->body);
        return Response::ok($r);
    } catch (HttpException $e) {
        return Response::error($e->getMessage(), $e->getCode());
    }
}, [[Csrf::class, 'middleware']]);

/* -------------------------------- Admin -------------------------------- */
require __DIR__ . '/admin/index.php';

// Despachar
try {
    $response = $router->dispatch($req);
    $response->send();
} catch (\Throwable $e) {
    HttpExceptionHandler::handle($e)->send();
}