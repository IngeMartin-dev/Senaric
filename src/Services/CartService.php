<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Core/Sanitizer.php';
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

    public static function all(): array
    {
        Session::start();
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public static function add(int $productId, int $quantity = 1): array
    {
        if ($productId < 1 || $quantity < 1) {
            throw new InvalidArgumentException('Producto o cantidad inválidos.');
        }
        $items = self::all();
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

        // Validación suave: no superar el stock del producto.
        $hydrated = self::hydrate();
        foreach ($hydrated as $line) {
            if ($line['quantity'] > $line['stock']) {
                throw new RuntimeException(sprintf('No hay suficiente stock para "%s". Disponible: %d.', $line['name'], $line['stock']));
            }
        }
        return $hydrated;
    }

    public static function update(int $productId, int $quantity): array
    {
        $items = self::all();
        $items = array_values(array_filter($items, fn($i) => (int) $i['product_id'] !== $productId));
        if ($quantity > 0) {
            $items[] = ['product_id' => $productId, 'quantity' => $quantity];
        }
        Session::set(self::SESSION_KEY, $items);
        return self::hydrate();
    }

    public static function remove(int $productId): array
    {
        $items = self::all();
        $items = array_values(array_filter($items, fn($i) => (int) $i['product_id'] !== $productId));
        Session::set(self::SESSION_KEY, $items);
        return self::hydrate();
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