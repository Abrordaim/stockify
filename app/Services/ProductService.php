<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get all products with relations.
     */
    public function getAllProducts(): Collection
    {
        return $this->productRepository->getAllWithRelations();
    }

    /**
     * Get paginated products with relations.
     */
    public function getPaginatedProducts(int $perPage = 15)
    {
        return $this->productRepository->getPaginatedWithRelations($perPage);
    }

    /**
     * Get products by category.
     */
    public function getProductsByCategory(int $categoryId): Collection
    {
        return $this->productRepository->getByCategory($categoryId);
    }

    /**
     * Get products by supplier.
     */
    public function getProductsBySupplier(int $supplierId): Collection
    {
        return $this->productRepository->getBySupplier($supplierId);
    }

    /**
     * Search products by name or SKU.
     */
    public function searchProducts(string $keyword): Collection
    {
        return $this->productRepository->searchByNameOrSku($keyword);
    }

    /**
     * Find product by ID.
     */
    public function findProduct(int $id): ?Model
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Find product by SKU.
     */
    public function findProductBySku(string $sku): ?Product
    {
        return $this->productRepository->findBySku($sku);
    }

    /**
     * Create a new product with optional attributes.
     */
    public function createProduct(array $data, array $attributes = []): Model
    {
        $product = $this->productRepository->create($data);

        // Create product attributes if provided
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $product->productAttributes()->create($attribute);
            }
        }

        return $product->load(['category', 'supplier', 'productAttributes']);
    }

    /**
     * Update an existing product with optional attributes.
     */
    public function updateProduct(int $id, array $data, array $attributes = []): Model
    {
        $product = $this->productRepository->update($id, $data);

        // Sync product attributes if provided
        if (!empty($attributes)) {
            // Delete existing attributes and recreate
            $product->productAttributes()->delete();

            foreach ($attributes as $attribute) {
                $product->productAttributes()->create($attribute);
            }
        }

        return $product->load(['category', 'supplier', 'productAttributes']);
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    /**
     * Get products with low stock.
     */
    public function getLowStockProducts(): Collection
    {
        return $this->productRepository->getLowStockProducts();
    }

    /**
     * Count total products.
     */
    public function countProducts(): int
    {
        return $this->productRepository->count();
    }
}
