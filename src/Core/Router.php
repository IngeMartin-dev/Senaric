<?php
declare(strict_types=1);

/**
 * Router minimalista basado en closure.
 *
 * - Registra rutas por método + path.
 * - Soporta parámetros {nombre}.
 * - Permite middlewares por ruta o globales.
 */
final class Router
{
    /** @var array<string, array<int, array{pattern:string, params:array<string>, handler:callable, middlewares:array}>> */
    private array $routes = [];
    /** @var callable[] */
    private array $globalMiddlewares = [];
    private string $prefix;

    public function __construct(string $prefix = '')
    {
        $this->prefix = rtrim($prefix, '/');
    }

    public function middleware(callable $mw): void
    {
        $this->globalMiddlewares[] = $mw;
    }

    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $fullPath = $this->prefix . '/' . ltrim($path, '/');
        $pattern = $this->compile($fullPath);

        $this->routes[strtoupper($method)][] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'path' => $fullPath,
        ];
    }

    public function get(string $p, callable $h, array $m = []): void { $this->add('GET', $p, $h, $m); }
    public function post(string $p, callable $h, array $m = []): void { $this->add('POST', $p, $h, $m); }
    public function put(string $p, callable $h, array $m = []): void { $this->add('PUT', $p, $h, $m); }
    public function patch(string $p, callable $h, array $m = []): void { $this->add('PATCH', $p, $h, $m); }
    public function delete(string $p, callable $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    public function dispatch(Request $req): Response
    {
        $method = $req->method;
        $path = $req->path;

        // Soportar método override por _method (formularios HTML)
        if ($method === 'POST' && isset($req->body['_method'])) {
            $override = strtoupper((string) $req->body['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $candidates = $this->routes[$method] ?? [];
        foreach ($candidates as $route) {
            if (preg_match($route['pattern'], $path, $m)) {
                $params = [];
                foreach ($m as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = $v;
                    }
                }
                $req->routeParams = $params;

                $chain = array_merge($this->globalMiddlewares, $route['middlewares']);
                return $this->runChain($chain, $req, fn() => ($route['handler'])($req));
            }
        }

        return Response::error('Ruta no encontrada', 404);
    }

    private function runChain(array $chain, Request $req, callable $core): Response
    {
        // El core debe invocarse como $core($req)
        $next = static fn(Request $r): Response => $core($r);
        foreach (array_reverse($chain) as $mw) {
            $prev = $next;
            $next = static fn(Request $r): Response => $mw($r, $prev);
        }
        return $next($req);
    }

    private function compile(string $path): string
    {
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);
        return '#^' . $regex . '$#';
    }
}