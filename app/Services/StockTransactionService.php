<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
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
    /**
     * Record a stock-in transaction (barang masuk).
     */
    public function recordStockIn(array $data): Model
    {
        $product = $this->productRepository->findOrFail($data['product_id']);
        $currentStock = $product->current_stock;

        $rawStatus = $data['status'] ?? 'Pending';
        $status = match (strtolower($rawStatus)) {
            'completed', 'diterima' => 'Diterima',
            'cancelled', 'ditolak' => 'Ditolak',
            default => 'Pending',
        };

        $isConfirmed = ($status === 'Diterima');
        $stockBefore = $currentStock;
        // Kalau status Pending atau Ditolak, stok sebelum dan sesudah HARUS sama (belum berubah)
        $stockAfter = $isConfirmed ? ($currentStock + (int) $data['quantity']) : $currentStock;

        $createdBy = $data['created_by'] ?? $data['user_id'] ?? auth()->id();
        $data['created_by'] = $createdBy;
        $data['user_id'] = $createdBy;
        $data['confirmed_by'] = $isConfirmed ? ($data['confirmed_by'] ?? $createdBy) : null;
        $data['confirmed_at'] = $isConfirmed ? now() : null;
        $data['status'] = $status;
        $data['stock_before'] = $stockBefore;
        $data['stock_after'] = $stockAfter;
        $data['type'] = 'in';

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
        $product = $this->productRepository->findOrFail($data['product_id']);
        $currentStock = $product->current_stock;

        $rawStatus = $data['status'] ?? 'Pending';
        $status = match (strtolower($rawStatus)) {
            'completed', 'dikeluarkan' => 'Dikeluarkan',
            'cancelled', 'ditolak' => 'Ditolak',
            default => 'Pending',
        };

        if ($status === 'Dikeluarkan' && $currentStock < (int) $data['quantity']) {
            throw new \Exception("Stok tidak mencukupi untuk mengeluarkan barang. Stok saat ini: {$currentStock}, diminta: {$data['quantity']}");
        }

        $isConfirmed = ($status === 'Dikeluarkan');
        $stockBefore = $currentStock;
        // Kalau status Pending atau Ditolak, stok sebelum dan sesudah HARUS sama (belum berubah)
        $stockAfter = $isConfirmed ? ($currentStock - (int) $data['quantity']) : $currentStock;

        $createdBy = $data['created_by'] ?? $data['user_id'] ?? auth()->id();
        $data['created_by'] = $createdBy;
        $data['user_id'] = $createdBy;
        $data['confirmed_by'] = $isConfirmed ? ($data['confirmed_by'] ?? $createdBy) : null;
        $data['confirmed_at'] = $isConfirmed ? now() : null;
        $data['status'] = $status;
        $data['stock_before'] = $stockBefore;
        $data['stock_after'] = $stockAfter;
        $data['type'] = 'out';

        return $this->transactionRepository->create($data);
    }

    /**
     * Record a stock adjustment transaction (penyesuaian stok / stock opname).
     * Quantity can be positive (add) or negative (subtract).
     */
    public function recordAdjustment(array $data): Model
    {
        $product = $this->productRepository->findOrFail($data['product_id']);
        $currentStock = $product->current_stock;
        $qty = (int) $data['quantity'];
        $stockAfter = $currentStock + $qty;

        $createdBy = $data['created_by'] ?? $data['user_id'] ?? auth()->id();
        $data['created_by'] = $createdBy;
        $data['user_id'] = $createdBy;
        $data['confirmed_by'] = $data['confirmed_by'] ?? $createdBy;
        $data['confirmed_at'] = now();
        $data['status'] = 'Diterima';
        $data['stock_before'] = $currentStock;
        $data['stock_after'] = $stockAfter;
        $data['type'] = 'adjustment';

        return $this->transactionRepository->create($data);
    }

    /**
     * Confirm a pending transaction (executed by Staff Gudang).
     * Status becomes 'Diterima' (in) or 'Dikeluarkan' (out).
     *
     * @throws \Exception
     */
    public function confirmTransaction(int $id, ?int $confirmedByUserId = null): Model
    {
        $transaction = $this->transactionRepository->findOrFail($id);

        if (!in_array($transaction->status, ['Pending', 'pending'])) {
            throw new \Exception("Hanya transaksi berstatus 'Pending' yang dapat dikonfirmasi.");
        }

        $product = $this->productRepository->findOrFail($transaction->product_id);
        $currentStock = $product->current_stock;

        $newStatus = match ($transaction->type) {
            'in' => 'Diterima',
            'out' => 'Dikeluarkan',
            default => 'Diterima',
        };

        if ($transaction->type === 'out' && $currentStock < $transaction->quantity) {
            throw new \Exception("Stok tidak mencukupi untuk mengeluarkan barang. Stok saat ini: {$currentStock}, diminta: {$transaction->quantity}");
        }

        $stockBefore = $currentStock;
        $stockAfter = $transaction->type === 'in'
            ? ($currentStock + $transaction->quantity)
            : ($transaction->type === 'out' ? ($currentStock - $transaction->quantity) : $currentStock);

        $confirmedBy = $confirmedByUserId ?? auth()->id();

        return $this->transactionRepository->update($id, [
            'status' => $newStatus,
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);
    }

    /**
     * Reject a pending transaction (executed by Staff Gudang).
     * Status becomes 'Ditolak' and stock_before == stock_after (no stock change).
     *
     * @throws \Exception
     */
    public function rejectTransaction(int $id, ?int $confirmedByUserId = null, ?string $reason = null): Model
    {
        $transaction = $this->transactionRepository->findOrFail($id);

        if (!in_array($transaction->status, ['Pending', 'pending'])) {
            throw new \Exception("Hanya transaksi berstatus 'Pending' yang dapat ditolak.");
        }

        $product = $this->productRepository->findOrFail($transaction->product_id);
        $currentStock = $product->current_stock;

        $notes = $transaction->notes;
        if ($reason) {
            $notes = $notes ? "{$notes} (Ditolak: {$reason})" : "Ditolak: {$reason}";
        }

        $confirmedBy = $confirmedByUserId ?? auth()->id();

        return $this->transactionRepository->update($id, [
            'status' => 'Ditolak',
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
            'stock_before' => $currentStock,
            'stock_after' => $currentStock, // Tetap sama saat ditolak
            'notes' => $notes,
        ]);
    }

    /**
     * Update transaction status (bridges completed/cancelled to new official statuses).
     *
     * @throws \Exception
     */
    public function updateTransactionStatus(int $id, string $status, ?int $confirmedByUserId = null): Model
    {
        $normStatus = strtolower($status);
        if (in_array($normStatus, ['completed', 'diterima', 'dikeluarkan'])) {
            return $this->confirmTransaction($id, $confirmedByUserId);
        }

        if (in_array($normStatus, ['cancelled', 'ditolak'])) {
            return $this->rejectTransaction($id, $confirmedByUserId);
        }

        return $this->transactionRepository->update($id, ['status' => $status]);
    }

    /**
     * Get pending transactions (e.g. for staff task queue).
     */
    public function getPendingTransactions(?string $type = null): Collection
    {
        $transactions = $this->transactionRepository->getAllWithRelations()
            ->filter(fn($tx) => in_array($tx->status, ['Pending', 'pending']));

        if ($type) {
            return $transactions->where('type', $type)->values();
        }
        return $transactions->values();
    }

    /**
     * Get summary counts for dashboard.
     */
    public function getDashboardSummary(): array
    {
        $today = now()->toDateString();
        $todayTransactions = $this->transactionRepository->getByDateRange($today, $today);

        return [
            'total_transactions' => $this->transactionRepository->count(),
            'today_in' => $todayTransactions->where('type', 'in')->whereIn('status', ['Diterima', 'completed'])->count(),
            'today_out' => $todayTransactions->where('type', 'out')->whereIn('status', ['Dikeluarkan', 'completed'])->count(),
            'pending_count' => $this->transactionRepository->getAllWithRelations()
                ->whereIn('status', ['Pending', 'pending'])->count(),
        ];
    }
    /**
     * Resolve date range from a period string.
     *
     * @return array{0: string, 1: string} [startDate, endDate]
     */
    public function resolvePeriodDates(string $period): array
    {
        $today = now()->toDateString();

        return match ($period) {
            'today' => [$today, $today],
            '30_days' => [now()->subDays(29)->toDateString(), $today],
            'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'this_year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [now()->subDays(6)->toDateString(), $today], // '7_days'
        };
    }

    /**
     * Get transaction summary counts & quantities for a specific period.
     */
    public function getTransactionStatsByPeriod(string $period = '7_days'): array
    {
        [$startDate, $endDate] = $this->resolvePeriodDates($period);
        $transactions = $this->transactionRepository->getByDateRange($startDate, $endDate);

        $inTx = $transactions->where('type', 'in')->filter(fn($tx) => in_array($tx->status, ['Diterima', 'completed']));
        $outTx = $transactions->where('type', 'out')->filter(fn($tx) => in_array($tx->status, ['Dikeluarkan', 'completed']));

        $inCount = $inTx->count();
        $inQty = (int) $inTx->sum('quantity');

        $outCount = $outTx->count();
        $outQty = (int) $outTx->sum('quantity');

        $netQty = $inQty - $outQty;

        $periodLabels = [
            'today' => 'Hari Ini',
            '7_days' => '7 Hari Terakhir',
            '30_days' => '30 Hari Terakhir',
            'this_month' => 'Bulan Ini',
            'this_year' => 'Tahun Ini',
        ];

        return [
            'period' => $period,
            'period_label' => $periodLabels[$period] ?? 'Periode Ini',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'in_count' => $inCount,
            'in_qty' => $inQty,
            'out_count' => $outCount,
            'out_qty' => $outQty,
            'net_qty' => $netQty,
        ];
    }

    /**
     * Generate chart data series for stock mutations (Inbound vs Outbound).
     */
    public function getChartDataByPeriod(string $period = '7_days'): array
    {
        $categories = [];
        $inData = [];
        $outData = [];

        if ($period === 'this_year') {
            $start = now()->startOfYear();
            $end = now();
            $current = $start->copy();

            $transactions = $this->transactionRepository->getByDateRange(
                $start->toDateString(),
                now()->endOfYear()->toDateString()
            );

            while ($current->lte($end)) {
                $monthStr = $current->translatedFormat('M');
                $monthNum = $current->month;
                $yearNum = $current->year;

                $categories[] = $monthStr;

                $monthTx = $transactions->filter(function ($tx) use ($monthNum, $yearNum) {
                    $d = \Carbon\Carbon::parse($tx->date);
                    return $d->month === $monthNum && $d->year === $yearNum;
                });

                $inData[] = (int) $monthTx->where('type', 'in')->filter(fn($tx) => in_array($tx->status, ['Diterima', 'completed']))->sum('quantity');
                $outData[] = (int) $monthTx->where('type', 'out')->filter(fn($tx) => in_array($tx->status, ['Dikeluarkan', 'completed']))->sum('quantity');

                $current->addMonth();
            }
        } else {
            [$startDate, $endDate] = $this->resolvePeriodDates($period);
            if ($period === 'today') {
                $startDate = now()->subDays(6)->toDateString();
            }

            $transactions = $this->transactionRepository->getByDateRange($startDate, $endDate);

            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->toDateString();
                $label = $date->translatedFormat('d M');

                $categories[] = $label;

                $dayTx = $transactions->filter(function ($tx) use ($dateStr) {
                    return \Carbon\Carbon::parse($tx->date)->toDateString() === $dateStr;
                });

                $inData[] = (int) $dayTx->where('type', 'in')->filter(fn($tx) => in_array($tx->status, ['Diterima', 'completed']))->sum('quantity');
                $outData[] = (int) $dayTx->where('type', 'out')->filter(fn($tx) => in_array($tx->status, ['Dikeluarkan', 'completed']))->sum('quantity');
            }
        }

        return [
            'categories' => $categories,
            'inSeries' => $inData,
            'outSeries' => $outData,
            'totalIn' => array_sum($inData),
            'totalOut' => array_sum($outData),
        ];
    }

    /**
     * Get recent user activities for audit trail feed.
     */
    public function getRecentUserActivities(int $limit = 8): SupportCollection
    {
        $transactions = $this->transactionRepository->getRecent($limit);

        return $transactions->map(function ($tx) {
            $actor = $tx->confirmedBy ?: ($tx->createdBy ?: $tx->user);
            $product = $tx->product;

            $actionText = match ($tx->type) {
                'in' => match ($tx->status) {
                    'Ditolak' => 'Menolak Penerimaan Barang',
                    'Pending' => 'Mencatat Rencana Barang Masuk',
                    default => 'Menerima Barang Masuk',
                },
                'out' => match ($tx->status) {
                    'Ditolak' => 'Menolak Pengeluaran Barang',
                    'Pending' => 'Mencatat Permintaan Barang Keluar',
                    default => 'Mengeluarkan Barang',
                },
                'adjustment' => 'Melakukan Stock Opname',
                default => 'Mencatat Transaksi Stok',
            };

            $typeColor = match ($tx->type) {
                'in' => 'emerald',
                'out' => 'amber',
                'adjustment' => 'purple',
                default => 'blue',
            };

            $createdAt = $tx->confirmed_at ?: ($tx->created_at ?: ($tx->date ? \Carbon\Carbon::parse($tx->date) : now()));
            $actorName = $actor->name ?? 'Petugas Gudang';
            $actorRole = $actor->role ?? 'staff';

            return (object) [
                'id' => $tx->id,
                'user_name' => $actorName,
                'user_role' => $actorRole,
                'user_initials' => strtoupper(substr($actorName, 0, 2)),
                'action_text' => $actionText,
                'type' => $tx->type,
                'type_color' => $typeColor,
                'product_name' => $product->name ?? 'Produk',
                'product_sku' => $product->sku ?? '-',
                'quantity' => $tx->quantity,
                'status' => $tx->status,
                'stock_before' => $tx->stock_before,
                'stock_after' => $tx->stock_after,
                'notes' => $tx->notes,
                'date' => $tx->date,
                'time_ago' => $createdAt->diffForHumans(),
            ];
        });
    }
}
