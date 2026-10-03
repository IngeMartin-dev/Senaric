<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Sanitizer.php';
require_once __DIR__ . '/../Core/Validator.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Repositories/ProductRepository.php';

final class ProductService
{
    public function __construct(private ProductRepository $repo = new ProductRepository()) {}

    public function list(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(48, $perPage));
        $clean = [];
        if (!empty($filters['category'])) {
            $clean['category'] = Sanitizer::int($filters['category']);
        }
        if (!empty($filters['search'])) {
            $clean['search'] = Sanitizer::string($filters['search'], 80);
        }
        return $this->repo->paginated($clean, $page, $perPage);
    }

    public function show(string $slug): array
    {
        $product = $this->repo->findBySlug(Sanitizer::slug($slug));
        if (!$product) {
            throw new HttpException(404, 'Producto no encontrado.');
        }
        return $product;
    }

    public function create(array $input): array
    {
        $errors = Validator::check($input, [
            'name' => 'required|string|min:2|max:180',
            'slug' => 'required|slug|max:180',
            'price' => 'required|numeric|min:0',
            'stock' => 'integer|min:0',
            'category_id' => 'integer|min:1',
            'short_description' => 'string|max:280',
            'description' => 'string|max:5000',
            'image_url' => 'string|max:500',
            'featured' => 'in:true,false,0,1',
            'status' => 'in:active,draft,archived',
        ]);
        if (!empty($errors)) {
            throw new HttpException(422, 'Datos de producto inválidos.');
        }

        return $this->repo->create([
            'name' => Sanitizer::string($input['name'], 180),
            'slug' => Sanitizer::slug($input['slug']),
            'short_description' => Sanitizer::string($input['short_description'] ?? '', 280),
            'description' => Sanitizer::richText($input['description'] ?? ''),
            'price' => Sanitizer::float($input['price'], 0),
            'compare_price' => Sanitizer::float($input['compare_price'] ?? 0, 0),
            'stock' => Sanitizer::int($input['stock'] ?? 0, 0, 100000),
            'category_id' => Sanitizer::int($input['category_id'] ?? 0, 0, PHP_INT_MAX),
            'image_url' => Sanitizer::url($input['image_url'] ?? ''),
            'featured' => filter_var($input['featured'] ?? false, FILTER_VALIDATE_BOOL),
            'status' => Sanitizer::string($input['status'] ?? 'active', 20),
        ]);
    }

    public function update(int $id, array $input): array
    {
        $existing = $this->repo->find($id);
        if (!$existing) {
            throw new HttpException(404, 'Producto no encontrado.');
        }

        $errors = Validator::check($input, [
            'name' => 'string|min:2|max:180',
            'slug' => 'slug|max:180',
            'price' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'category_id' => 'integer|min:1',
            'short_description' => 'string|max:280',
            'description' => 'string|max:5000',
            'image_url' => 'string|max:500',
            'featured' => 'in:true,false,0,1',
            'status' => 'in:active,draft,archived',
        ]);
        if (!empty($errors)) {
            throw new HttpException(422, 'Datos de producto inválidos.');
        }

        $payload = [];
        if (isset($input['name'])) $payload['name'] = Sanitizer::string($input['name'], 180);
        if (isset($input['slug'])) $payload['slug'] = Sanitizer::slug($input['slug']);
        if (isset($input['short_description'])) $payload['short_description'] = Sanitizer::string($input['short_description'], 280);
        if (isset($input['description'])) $payload['description'] = Sanitizer::richText($input['description']);
        if (isset($input['price'])) $payload['price'] = Sanitizer::float($input['price'], 0);
        if (isset($input['compare_price'])) $payload['compare_price'] = Sanitizer::float($input['compare_price'], 0);
        if (isset($input['stock'])) $payload['stock'] = Sanitizer::int($input['stock'], 0, 100000);
        if (isset($input['category_id'])) $payload['category_id'] = Sanitizer::int($input['category_id'], 1);
        if (isset($input['image_url'])) $payload['image_url'] = Sanitizer::url($input['image_url']);
        if (isset($input['featured'])) $payload['featured'] = filter_var($input['featured'], FILTER_VALIDATE_BOOL);
        if (isset($input['status'])) $payload['status'] = Sanitizer::string($input['status'], 20);

        return $this->repo->update($id, $payload);
    }

    public function delete(int $id): void
    {
        $existing = $this->repo->find($id);
        if (!$existing) {
            throw new HttpException(404, 'Producto no encontrado.');
        }
        $this->repo->delete($id);
    }
}