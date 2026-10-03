<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/supabase.php';

final class OrderRepository
{
    private Supabase $db;

    public function __construct(?Supabase $client = null)
    {
        $this->db = $client ?? Supabase::admin();
    }

    public function listByUser(string $userId): array
    {
        return $this->db->select('orders', [
            'select' => '*,items:order_items(*,product:product_id(id,slug,name,image_url))',
            'eq' => ['user_id' => $userId],
            'order' => 'created_at.desc',
        ]);
    }

    public function find(int $id): ?array
    {
        return $this->db->find('orders', $id, '*,items:order_items(*,product:product_id(id,slug,name,image_url))');
    }

    public function createOrder(array $order, array $items): array
    {
        // Se hace en dos pasos: cabecera + items. Para entornos de alto tráfico
        // lo correcto es usar una RPC transaccional; lo dejamos preparado.
        $rows = $this->db->insert('orders', [$order], true);
        $orderRow = $rows[0] ?? null;
        if (!$orderRow || empty($orderRow['id'])) {
            throw new RuntimeException('No se pudo crear el pedido.');
        }

        $orderId = (int) $orderRow['id'];
        $payload = [];
        foreach ($items as $item) {
            $payload[] = [
                'order_id' => $orderId,
                'product_id' => (int) $item['product_id'],
                'product_name' => (string) ($item['product_name'] ?? ''),
                'unit_price' => (float) $item['unit_price'],
                'quantity' => (int) $item['quantity'],
                'subtotal' => (float) $item['subtotal'],
            ];
        }
        if ($payload) {
            $this->db->insert('order_items', $payload, false);
        }

        $orderRow['items'] = $payload;
        return $orderRow;
    }

    public function updateStatus(int $id, string $status): array
    {
        $rows = $this->db->update('orders', ['status' => $status], ['eq' => ['id' => $id]]);
        return $rows[0] ?? [];
    }
}