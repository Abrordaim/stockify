<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StockTransactionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected StockTransactionService $stockService,
        protected CategoryService $categoryService
    ) {}

    /**
     * Export Stock Report to Excel-compatible CSV.
     */
    public function exportStockCsv(Request $request): StreamedResponse
    {
        $products = $this->productService->getAllProducts();

        // Apply category filter if present
        if ($request->filled('category_id')) {
            $products = $products->where('category_id', (int) $request->category_id);
        }

        $filename = 'Laporan_Stok_Stockify_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'No',
                'SKU',
                'Nama Produk',
                'Kategori',
                'Supplier',
                'Harga Beli (Rp)',
                'Harga Jual (Rp)',
                'Batas Min. Stok',
                'Stok Fisik Saat Ini',
                'Total Nilai Aset (Rp)',
                'Status Stok',
            ]);

            $no = 1;
            foreach ($products as $p) {
                $totalAsset = $p->purchase_price * $p->current_stock;
                fputcsv($handle, [
                    $no++,
                    $p->sku,
                    $p->name,
                    $p->category->name ?? '-',
                    $p->supplier->name ?? '-',
                    number_format($p->purchase_price, 0, ',', '.'),
                    number_format($p->selling_price, 0, ',', '.'),
                    $p->minimum_stock,
                    $p->current_stock,
                    number_format($totalAsset, 0, ',', '.'),
                    $p->is_low_stock ? 'MENIPIS' : 'AMAN',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print / PDF View for Stock Report.
     */
    public function printStock(Request $request)
    {
        $products = $this->productService->getAllProducts();

        if ($request->filled('category_id')) {
            $products = $products->where('category_id', (int) $request->category_id);
        }

        return view('reports.stock-print', [
            'products' => $products,
            'generatedAt' => now()->translatedFormat('d F Y, H:i'),
            'generatedBy' => auth()->user()->name,
        ]);
    }

    /**
     * Export Stock Mutations to Excel-compatible CSV.
     */
    public function exportMutationsCsv(Request $request): StreamedResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $type = $request->get('type');

        $transactions = $this->stockService->getTransactionsByDateRange($startDate, $endDate);

        if ($type) {
            $transactions = $transactions->where('type', $type);
        }

        $filename = 'Laporan_Mutasi_Stok_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Tanggal',
                'SKU',
                'Nama Produk',
                'Tipe Mutasi',
                'Kuantitas (Unit)',
                'Dicatat Oleh',
                'Status',
                'Keterangan / Catatan',
            ]);

            $no = 1;
            foreach ($transactions as $tx) {
                $typeLabel = match($tx->type) {
                    'in' => 'Barang Masuk',
                    'out' => 'Barang Keluar',
                    'adjustment' => 'Stock Opname',
                    default => $tx->type,
                };

                fputcsv($handle, [
                    $no++,
                    $tx->date ? $tx->date->format('d/m/Y') : '-',
                    $tx->product->sku ?? '-',
                    $tx->product->name ?? '-',
                    $typeLabel,
                    ($tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : '')) . $tx->quantity,
                    $tx->user->name ?? '-',
                    strtoupper($tx->status),
                    $tx->notes ?: '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print / PDF View for Mutations Report.
     */
    public function printMutations(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $type = $request->get('type');

        $transactions = $this->stockService->getTransactionsByDateRange($startDate, $endDate);

        if ($type) {
            $transactions = $transactions->where('type', $type);
        }

        return view('reports.mutations-print', [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type,
            'generatedAt' => now()->translatedFormat('d F Y, H:i'),
            'generatedBy' => auth()->user()->name,
        ]);
    }
}
