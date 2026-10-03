<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/supabase.php';

final class CategoryRepository
{
    private Supabase $db;

    public function __construct(?Supabase $client = null)
    {
        $this->db = $client ?? Supabase::admin();
    }

    public function all(): array
    {
        return $this->db->select('categories', [
            'select' => 'id,slug,name,description,image_url',
            'order' => 'name.asc',
        ]);
    }

    public function find(int $id): ?array
    {
        return $this->db->find('categories', $id);
    }

    public function findBySlug(string $slug): ?array
    {
        $rows = $this->db->select('categories', [
            'select' => '*',
            'eq' => ['slug' => $slug],
            'limit' => 1,
        ]);
        return $rows[0] ?? null;
    }

    public function create(array $data): array
    {
        $rows = $this->db->insert('categories', [$this->normalize($data)], true);
        return $rows[0] ?? [];
    }

    public function update(int $id, array $data): array
    {
        $rows = $this->db->update('categories', $this->normalize($data), ['eq' => ['id' => $id]]);
        return $rows[0] ?? [];
    }

    public function delete(int $id): bool
    {
        $rows = $this->db->delete('categories', ['eq' => ['id' => $id]]);
        return !empty($rows);
    }

    private function normalize(array $data): array
    {
        $out = [];
        foreach (['slug', 'name', 'description', 'image_url'] as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $data[$k];
            }
        }
        return $out;
    }
}