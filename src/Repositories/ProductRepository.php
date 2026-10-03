<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/supabase.php';

/**
 * Acceso a datos de productos.
 * Todas las operaciones usan el cliente admin (service role) porque el
 * backend valida autorización por su cuenta.
 */
final class ProductRepository
{
    private Supabase $db;

    public function __construct(?Supabase $client = null)
    {
        $this->db = $client ?? Supabase::admin();
    }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $opts = [
            'select' => 'id,slug,name,short_description,price,compare_price,stock,featured,image_url,category_id,categories:category_id(id,slug,name),created_at',
            'order' => 'created_at.desc',
            'limit' => $perPage,
            'offset' => max(0, ($page - 1) * $perPage),
        ];

        if (!empty($filters['category'])) {
            $opts['eq'] = ['category_id' => (int) $filters['category']];
        }
        if (!empty($filters['search'])) {
            $opts['or'] = sprintf(
                '(name.ilike.*%s*,short_description.ilike.*%s*)',
                rawurlencode((string) $filters['search']),
                rawurlencode((string) $filters['search'])
            );
        }
        if (array_key_exists('featured', $filters)) {
            $opts['eq'] = array_merge($opts['eq'] ?? [], ['featured' => filter_var($filters['featured'], FILTER_VALIDATE_BOOL) ? 'true' : 'false']);
        }

        $items = $this->db->select('products', $opts);

        // Total
        $countOpts = ['select' => 'id', 'limit' => 1];
        if (!empty($opts['eq'])) {
            $countOpts['eq'] = $opts['eq'];
        }
        if (!empty($opts['or'])) {
            $countOpts['or'] = $opts['or'];
        }
        $countRows = $this->db->select('products', $countOpts);
        $total = is_array($countRows) ? count($countRows) : 0;
        // Nota: Supabase PostgREST devuelve filas para el count cuando no se
        // usa el header Prefer: count=exact. Como es una API simple, usamos
        // paginación estándar. Para catálogos enormes se recomienda usar
        // el header de conteo exacto en una llamada dedicada.

        return [
            'items' => $items,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function findBySlug(string $slug): ?array
    {
        $rows = $this->db->select('products', [
            'select' => '*,categories:category_id(id,slug,name)',
            'eq' => ['slug' => $slug],
            'limit' => 1,
        ]);
        return $rows[0] ?? null;
    }

    public function find(int $id): ?array
    {
        return $this->db->find('products', $id, '*,categories:category_id(id,slug,name)');
    }

    public function create(array $data): array
    {
        $rows = $this->db->insert('products', [$this->normalize($data)], true);
        return $rows[0] ?? [];
    }

    public function update(int $id, array $data): array
    {
        $rows = $this->db->update('products', $this->normalize($data), ['eq' => ['id' => $id]]);
        return $rows[0] ?? [];
    }

    public function delete(int $id): bool
    {
        $rows = $this->db->delete('products', ['eq' => ['id' => $id]]);
        return !empty($rows);
    }

    public function decrementStock(int $id, int $qty): void
    {
        // Se hace mediante una función RPC "decrement_stock" definida en
        // database/schema.sql para garantizar atomicidad y validación.
        try {
            $this->db->rpc('decrement_stock', ['p_id' => $id, 'p_qty' => $qty]);
        } catch (\Throwable $e) {
            // Si la función no está creada todavía, el sistema sigue funcionando;
            // se ignora en silencio y se registra.
            Logger::warning('decrement_stock_failed', ['id' => $id, 'qty' => $qty, 'error' => $e->getMessage()]);
        }
    }

    private function normalize(array $data): array
    {
        $allowed = ['category_id', 'slug', 'name', 'short_description', 'description', 'price', 'compare_price', 'stock', 'featured', 'image_url', 'gallery', 'status'];
        $out = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $data[$k];
            }
        }
        return $out;
    }
}