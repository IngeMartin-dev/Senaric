<?php
declare(strict_types=1);

/**
 * Endpoints administrativos.
 * Requieren rol admin (ver Auth::requireAdmin).
 */

// Este archivo se incluye desde api/index.php dentro del mismo Router.

require_once dirname(__DIR__, 2) . '/src/Core/Sanitizer.php';
require_once dirname(__DIR__, 2) . '/src/Core/Validator.php';
require_once dirname(__DIR__, 2) . '/src/Core/Auth.php';

$router->get('/admin/products', function (Request $req) {
    Auth::requireAdmin($req);
    $service = new ProductService();
    $result = $service->list([
        'category' => $req->query['category'] ?? null,
        'search' => $req->query['search'] ?? null,
    ], (int) ($req->query['page'] ?? 1), (int) ($req->query['per_page'] ?? 50));
    return Response::ok($result);
});

$router->get('/admin/products/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $repo = new ProductRepository();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    $product = $repo->find($id);
    if (!$product) return Response::error('No encontrado', 404);
    return Response::ok($product);
});

$router->post('/admin/products', function (Request $req) {
    Auth::requireAdmin($req);
    $service = new ProductService();
    try {
        $product = $service->create($req->body);
        return Response::ok($product, 201);
    } catch (HttpException $e) {
        return Response::error($e->getMessage(), $e->getCode());
    }
});

$router->patch('/admin/products/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $service = new ProductService();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    try {
        $product = $service->update($id, $req->body);
        return Response::ok($product);
    } catch (HttpException $e) {
        return Response::error($e->getMessage(), $e->getCode());
    }
});

$router->put('/admin/products/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $service = new ProductService();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    try {
        $product = $service->update($id, $req->body);
        return Response::ok($product);
    } catch (HttpException $e) {
        return Response::error($e->getMessage(), $e->getCode());
    }
});

$router->delete('/admin/products/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $service = new ProductService();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    try {
        $service->delete($id);
        return Response::ok(['deleted' => true]);
    } catch (HttpException $e) {
        return Response::error($e->getMessage(), $e->getCode());
    }
});

$router->get('/admin/categories', function (Request $req) {
    Auth::requireAdmin($req);
    $repo = new CategoryRepository();
    return Response::ok($repo->all());
});

$router->post('/admin/categories', function (Request $req) {
    Auth::requireAdmin($req);
    $repo = new CategoryRepository();
    $errors = Validator::check($req->body, [
        'name' => 'required|string|min:2|max:120',
        'slug' => 'required|slug|max:120',
        'description' => 'string|max:280',
    ]);
    if ($errors) return Response::error('Datos inválidos', 422, ['fields' => $errors]);
    $cat = $repo->create([
        'name' => Sanitizer::string($req->body['name'], 120),
        'slug' => Sanitizer::slug($req->body['slug']),
        'description' => Sanitizer::string($req->body['description'] ?? '', 280),
    ]);
    return Response::ok($cat, 201);
});

$router->patch('/admin/categories/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $repo = new CategoryRepository();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    $payload = [];
    if (isset($req->body['name'])) $payload['name'] = Sanitizer::string($req->body['name'], 120);
    if (isset($req->body['slug'])) $payload['slug'] = Sanitizer::slug($req->body['slug']);
    if (isset($req->body['description'])) $payload['description'] = Sanitizer::string($req->body['description'], 280);
    if (!$payload) return Response::error('Nada que actualizar', 400);
    return Response::ok($repo->update($id, $payload));
});

$router->delete('/admin/categories/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $repo = new CategoryRepository();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    $repo->delete($id);
    return Response::ok(['deleted' => true]);
});

$router->get('/admin/orders', function (Request $req) {
    Auth::requireAdmin($req);
    $supa = Supabase::admin();
    $items = $supa->select('orders', [
        'select' => '*,items:order_items(*,product:product_id(id,slug,name,image_url))',
        'order' => 'created_at.desc',
        'limit' => min(200, (int) ($req->query['per_page'] ?? 100)),
    ]);
    return Response::ok(['items' => $items]);
});

$router->patch('/admin/orders/{id}', function (Request $req) {
    Auth::requireAdmin($req);
    $repo = new OrderRepository();
    $id = Sanitizer::int($req->routeParams['id'] ?? 0, 1);
    $status = Sanitizer::string($req->body['status'] ?? '', 20);
    if (!in_array($status, ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
        return Response::error('Estado no permitido', 422);
    }
    return Response::ok($repo->updateStatus($id, $status));
});