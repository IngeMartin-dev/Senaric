<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Sanitizer.php';
require_once __DIR__ . '/../Core/Validator.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/ProductRepository.php';
require_once 'CartService.php';

final class OrderService
{
    public function __construct(
        private OrderRepository $orders = new OrderRepository(),
        private ProductRepository $products = new ProductRepository(),
    ) {}

    public function checkout(array $user, array $checkoutData): array
    {
        $errors = Validator::check($checkoutData, [
            'full_name' => 'required|string|min:3|max:120',
            'phone' => 'required|string|min:6|max:40',
            'address' => 'required|string|min:8|max:250',
            'city' => 'required|string|min:2|max:80',
            'department' => 'required|string|min:2|max:80',
            'notes' => 'string|max:500',
        ]);
        if (!empty($errors)) {
            throw new HttpException(422, 'Datos de envío inválidos.');
        }

        $totals = CartService::totals();
        if (empty($totals['items'])) {
            throw new HttpException(400, 'Tu carrito está vacío.');
        }

        // Validar stock antes de crear el pedido
        if (!CartService::stockIsInfinite()) {
            foreach ($totals['items'] as $item) {
                if ($item['quantity'] > $item['stock']) {
                    throw new HttpException(409, sprintf('No hay suficiente stock para "%s".', $item['name']));
                }
            }
        }

        $order = [
            'user_id' => $user['id'],
            'customer_email' => $user['email'] ?? '',
            'full_name' => Sanitizer::string($checkoutData['full_name'], 120),
            'phone' => Sanitizer::string($checkoutData['phone'], 40),
            'address' => Sanitizer::string($checkoutData['address'], 250),
            'city' => Sanitizer::string($checkoutData['city'], 80),
            'department' => Sanitizer::string($checkoutData['department'], 80),
            'notes' => Sanitizer::string($checkoutData['notes'] ?? '', 500),
            'subtotal' => $totals['subtotal'],
            'shipping' => $totals['shipping'],
            'total' => $totals['total'],
            'currency' => 'COP',
            'status' => 'pending',
            'payment_method' => Sanitizer::string($checkoutData['payment_method'] ?? 'cod', 20),
        ];

        $itemsPayload = array_map(static function (array $item): array {
            return [
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['line_total'],
            ];
        }, $totals['items']);

        $created = $this->orders->createOrder($order, $itemsPayload);

        // Decrementar stock
        if (!CartService::stockIsInfinite()) {
            foreach ($totals['items'] as $item) {
                $this->products->decrementStock($item['product_id'], $item['quantity']);
            }
        }

        CartService::clear();

        return $created;
    }
}