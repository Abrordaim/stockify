<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockTransactionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get transactions with product and user loaded.
     */
    public function getAllWithRelations();

    /**
     * Get paginated transactions with relations.
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get transactions filtered by product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get transactions filtered by type (in/out/adjustment).
     */
    public function getByType(string $type): Collection;

    /**
     * Get transactions filtered by status (pending/completed/cancelled).
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get transactions filtered by date range.
     */
    public function getByDateRange(string $startDate, string $endDate);

    /**
     * Get transactions recorded by a specific user.
     */
    public function getByUser(int $userId): Collection;

    /**
     * Get recent transactions (for dashboard).
     */
    public function getRecent(int $limit = 10);
}
