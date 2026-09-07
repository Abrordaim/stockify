<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get products with their category and supplier loaded.
     */
    public function getAllWithRelations();

    /**
     * Get paginated products with relations.
     */
    public function getPaginatedWithRelations(int $perPage = 15);

    /**
     * Get products filtered by category.
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Get products filtered by supplier.
     */
    public function getBySupplier(int $supplierId): Collection;

    /**
     * Search products by name or SKU.
     */
    public function searchByNameOrSku(string $keyword);

    /**
     * Find product by SKU.
     */
    public function findBySku(string $sku);

    /**
     * Get products with low stock (current_stock <= minimum_stock).
     */
    public function getLowStockProducts(): Collection;
}
