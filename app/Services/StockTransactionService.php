<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockTransactionService
{
    public function __construct(
        protected StockTransactionRepositoryInterface $transactionRepository,
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get all transactions with relations.
     */
    public function getAllTransactions(): Collection
    {
        return $this->transactionRepository->getAllWithRelations();
    }

    /**
     * Get paginated transactions with relations.
     */
    public function getPaginatedTransactions(int $perPage = 15)
    {
        return $this->transactionRepository->getPaginatedWithRelations($perPage);
    }

    /**
     * Get transactions by product.
     */
    public function getTransactionsByProduct(int $productId): Collection
    {
        return $this->transactionRepository->getByProduct($productId);
    }

    /**
     * Get transactions by type.
     */
    public function getTransactionsByType(string $type): Collection
    {
        return $this->transactionRepository->getByType($type);
    }

    /**
     * Get transactions by date range.
     */
    public function getTransactionsByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->transactionRepository->getByDateRange($startDate, $endDate);
    }

    /**
     * Get recent transactions for dashboard.
     */
    public function getRecentTransactions(int $limit = 10): Collection
    {
        return $this->transactionRepository->getRecent($limit);
    }

    /**
     * Record a stock-in transaction (barang masuk).
     */
    public function recordStockIn(array $data): Model
    {
        $data['type'] = 'in';
        $data['status'] = $data['status'] ?? 'completed';

        return $this->transactionRepository->create($data);
    }

    /**
     * Record a stock-out transaction (barang keluar).
     * Validates that enough stock is available before proceeding.
     *
     * @throws \Exception
     */
    public function recordStockOut(array $data): Model
    {
        $data['type'] = 'out';
        $data['status'] = $data['status'] ?? 'completed';

        // Validate stock availability if transaction is completed
        if ($data['status'] === 'completed') {
            $product = $this->productRepository->findOrFail($data['product_id']);
            $currentStock = $product->current_stock;

            if ($currentStock < $data['quantity']) {
                throw new \Exception(
                    "Stok tidak mencukupi. Stok saat ini: {$currentStock}, diminta: {$data['quantity']}"
                );
            }
        }

        return $this->transactionRepository->create($data);
    }

    /**
     * Record a stock adjustment transaction (penyesuaian stok / stock opname).
     * Quantity can be positive (add) or negative (subtract).
     */
    public function recordAdjustment(array $data): Model
    {
        $data['type'] = 'adjustment';
        $data['status'] = $data['status'] ?? 'completed';

        return $this->transactionRepository->create($data);
    }

    /**
     * Update transaction status (e.g., pending → completed, pending → cancelled).
     *
     * @throws \Exception
     */
    public function updateTransactionStatus(int $id, string $status): Model
    {
        $transaction = $this->transactionRepository->findOrFail($id);

        // Prevent updating already completed/cancelled transactions
        if (in_array($transaction->status, ['completed', 'cancelled'])) {
            throw new \Exception(
                "Transaksi dengan status '{$transaction->status}' tidak dapat diubah."
            );
        }

        // Validate stock if completing an out transaction
        if ($status === 'completed' && $transaction->type === 'out') {
            $product = $this->productRepository->findOrFail($transaction->product_id);
            $currentStock = $product->current_stock;

            if ($currentStock < $transaction->quantity) {
                throw new \Exception(
                    "Stok tidak mencukupi untuk menyelesaikan transaksi. Stok saat ini: {$currentStock}, diminta: {$transaction->quantity}"
                );
            }
        }

        return $this->transactionRepository->update($id, ['status' => $status]);
    }

    /**
     * Get pending transactions (e.g. for staff task queue).
     */
    public function getPendingTransactions(?string $type = null): Collection
    {
        $transactions = $this->transactionRepository->getByStatus('pending');
        if ($type) {
            return $transactions->where('type', $type)->values();
        }
        return $transactions;
    }

    /**
     * Get summary counts for dashboard.
     */
    public function getDashboardSummary(): array
    {
        $today = now()->toDateString();

        return [
            'total_transactions' => $this->transactionRepository->count(),
            'today_in' => $this->transactionRepository->getByDateRange($today, $today)
                ->where('type', 'in')->count(),
            'today_out' => $this->transactionRepository->getByDateRange($today, $today)
                ->where('type', 'out')->count(),
            'pending_count' => $this->transactionRepository->getByStatus('pending')->count(),
        ];
    }
}
