<?php

namespace App\Repositories;

use App\Models\StockTransaction;
use App\Repositories\Contracts\StockTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StockTransactionRepository extends BaseRepository implements StockTransactionRepositoryInterface
{
    public function __construct(StockTransaction $model)
    {
        parent::__construct($model);
    }

    /**
     * Get transactions with product and user loaded.
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->with(['product', 'user'])->latest()->get();
    }

    /**
     * Get paginated transactions with relations.
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['product', 'user'])->latest()->paginate($perPage);
    }

    /**
     * Get transactions filtered by product.
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->with(['product', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Get transactions filtered by type (in/out/adjustment).
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)
            ->with(['product', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Get transactions filtered by status (pending/completed/cancelled).
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['product', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Get transactions filtered by date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('date', [$startDate, $endDate])
            ->with(['product', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Get transactions recorded by a specific user.
     */
    public function getByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->with(['product', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Get recent transactions (for dashboard).
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['product', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
