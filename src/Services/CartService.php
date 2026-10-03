<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Core/Sanitizer.php';
require_once __DIR__ . '/../Core/Auth.php'; // define HttpException
require_once __DIR__ . '/../Repositories/ProductRepository.php';

require_once dirname(__DIR__) . '/Helpers/view.php';

/**
 * Servicio de carrito de compras.
 *
 * El carrito se guarda en la sesión del usuario (incluso anónimo). En el
 * checkout se transfiere a un pedido (orders/order_items) en Supabase.
 */
final class CartService
{
    private const SESSION_KEY = 'cart.items';

    /**
     * Con STOCK_INFINITE=true el carrito ignora la disponibilidad: cualquier
     * cantidad (hasta 99 por línea) es aceptable.
     */
    public static function stockIsInfinite(): bool
    {
        return Env::bool('STOCK_INFINITE');
    }

    public static function all(): array
    {
        Session::start();
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public static function add(int $productId, int $quantity = 1): array
    {
        if ($productId < 1 || $quantity < 1) {
            throw new HttpException(422, 'Producto o cantidad inválidos.');
        }

        // Validar producto y stock ANTES de modificar el carrito.
        $product = (new ProductRepository())->find($productId);
        if (!$product || ($product['status'] ?? 'active') !== 'active') {
            throw new HttpException(404, 'Este producto ya no está disponible.');
        }
        $stock = (int) ($product['stock'] ?? 0);

        $items = self::all();
        $inCart = 0;
        foreach ($items as $item) {
            if ((int) $item['product_id'] === $productId) {
                $inCart = (int) $item['quantity'];
                break;
            }
        }
        if (!self::stockIsInfinite() && $inCart + $quantity > $stock) {
            throw new HttpException(409, sprintf(
                'No hay suficiente stock para "%s". Disponible: %d.',
                (string) ($product['name'] ?? 'este producto'),
                $stock
            ));
        }

        $found = false;
        foreach ($items as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) {
            $items[] = ['product_id' => $productId, 'quantity' => $quantity];
        }
        Session::set(self::SESSION_KEY, $items);

        return self::totals();
    }

    public static function update(int $productId, int $quantity): array
    {
        $quantity = max(0, $quantity);

        if ($quantity > 0) {
            $product = (new ProductRepository())->find($productId);
            if (!$product || ($product['status'] ?? 'active') !== 'active') {
                throw new HttpException(404, 'Este producto ya no está disponible.');
            }
            $stock = (int) ($product['stock'] ?? 0);
            if (!self::stockIsInfinite() && $quantity > $stock) {
                throw new HttpException(409, sprintf(
                    'No hay suficiente stock para "%s". Disponible: %d.',
                    (string) ($product['name'] ?? 'este producto'),
                    $stock
                ));
            }
        }

        // Se actualiza en su posición (sin mover el producto al final de la lista).
        $out = [];
        $found = false;
        foreach (self::all() as $item) {
            if ((int) $item['product_id'] === $productId) {
                $found = true;
                if ($quantity > 0) {
                    $item['quantity'] = $quantity;
                    $out[] = $item;
                }
                continue;
            }
            $out[] = $item;
        }
        if (!$found && $quantity > 0) {
            $out[] = ['product_id' => $productId, 'quantity' => $quantity];
        }

        Session::set(self::SESSION_KEY, $out);
        return self::totals();
    }

    public static function remove(int $productId): array
    {
        $items = self::all();
        $items = array_values(array_filter($items, fn($i) => (int) $i['product_id'] !== $productId));
        Session::set(self::SESSION_KEY, $items);
        return self::totals();
    }

    public static function clear(): void
    {
        Session::set(self::SESSION_KEY, []);
    }

    public static function totals(): array
    {
        $items = self::hydrate();
        $subtotal = 0.0;
        $count = 0;
        foreach ($items as $item) {
            $subtotal += $item['line_total'];
            $count += $item['quantity'];
        }
        $shipping = $subtotal >= 100000 || $subtotal === 0.0 ? 0.0 : 9000.0;
        $total = $subtotal + $shipping;
        return [
            'items' => $items,
            'count' => $count,
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'total' => round($total, 2),
            'currency' => 'COP',
        ];
    }

    /**
     * Enriquece los items del carrito con datos del producto.
     */
    public static function hydrate(): array
    {
        $items = self::all();
        if (empty($items)) return [];

        $repo = new ProductRepository();
        $out = [];
        foreach ($items as $item) {
            $product = $repo->find((int) $item['product_id']);
            if (!$product || ($product['status'] ?? 'active') !== 'active') {
                continue;
            }
            $unit = (float) ($product['price'] ?? 0);
            $qty = (int) $item['quantity'];
            $out[] = [
                'product_id' => (int) $product['id'],
                'slug' => (string) ($product['slug'] ?? ''),
                'name' => (string) ($product['name'] ?? ''),
                'image_url' => $product['image_url'] ?? null,
                'unit_price' => $unit,
                'quantity' => $qty,
                'line_total' => round($unit * $qty, 2),
                'stock' => (int) ($product['stock'] ?? 0),
            ];
        }
        return $out;
    }
}