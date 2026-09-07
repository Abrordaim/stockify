<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Get products with their category and supplier loaded.
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->with(['category', 'supplier', 'productAttributes'])->get();
    }

    /**
     * Get paginated products with relations.
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['category', 'supplier'])->latest()->paginate($perPage);
    }

    /**
     * Get products filtered by category.
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->where('category_id', $categoryId)
            ->with(['category', 'supplier'])
            ->get();
    }

    /**
     * Get products filtered by supplier.
     */
    public function getBySupplier(int $supplierId): Collection
    {
        return $this->model->where('supplier_id', $supplierId)
            ->with(['category', 'supplier'])
            ->get();
    }

    /**
     * Search products by name or SKU.
     */
    public function searchByNameOrSku(string $keyword): Collection
    {
        return $this->model->where('name', 'like', "%{$keyword}%")
            ->orWhere('sku', 'like', "%{$keyword}%")
            ->with(['category', 'supplier'])
            ->get();
    }

    /**
     * Find product by SKU.
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Get products with low stock (current_stock <= minimum_stock).
     * Uses subquery to calculate stock from completed transactions.
     */
    public function getLowStockProducts(): Collection
    {
        return $this->model->with(['category', 'supplier'])
            ->get()
            ->filter(function (Product $product) {
                return $product->is_low_stock;
            })
            ->values();
    }
}
