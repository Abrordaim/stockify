<?php

use App\Services\CategoryService;
use App\Services\ProductService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Laporan Stok & Valuasi Aset - Stockify')] class extends Component
{
    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';

    public function with(ProductService $productService, CategoryService $categoryService): array
    {
        $categories = $categoryService->getAllCategories();
        $products = $productService->getAllProducts();

        // Filter search
        if (!empty($this->search)) {
            $products = $products->filter(function($p) {
                return str_contains(strtolower($p->name), strtolower($this->search)) ||
                       str_contains(strtolower($p->sku), strtolower($this->search));
            });
        }

        // Filter category
        if (!empty($this->categoryFilter)) {
            $products = $products->where('category_id', (int) $this->categoryFilter);
        }

        // Filter stock status
        if ($this->statusFilter === 'low') {
            $products = $products->where('is_low_stock', true);
        } elseif ($this->statusFilter === 'safe') {
            $products = $products->where('is_low_stock', false);
        }

        // Compute metrics
        $totalItems = $products->count();
        $totalUnits = $products->sum('current_stock');
        $totalAssetValue = $products->sum(fn($p) => $p->purchase_price * $p->current_stock);
        $lowStockCount = $products->where('is_low_stock', true)->count();

        return [
            'products' => $products,
            'categories' => $categories,
            'totalItems' => $totalItems,
            'totalUnits' => $totalUnits,
            'totalAssetValue' => $totalAssetValue,
            'lowStockCount' => $lowStockCount,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header with Export Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">

                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Stok & Valuasi Aset</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Audit posisi stok fisik, batas minimum pengaman, dan total nilai aset barang di gudang.
                </p>
            </div>

            <!-- Export Buttons -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.stock.csv', ['category_id' => $categoryFilter]) }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel (CSV)
                </a>

                <a href="{{ route('reports.stock.print', ['category_id' => $categoryFilter]) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-500/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak / Simpan PDF
                </a>
            </div>
        </div>

        <!-- Metric Analytics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Item Produk -->
            <x-molecules.card title="total item" description="Jenis barang terdaftar" total="{{ $totalItems }}" color="blue"/>
            <x-molecules.card title="Total Fisik Barang" description="Unit tersedia di gudang" total="{{ number_format($totalUnits, 0, ',', '.') }}" color="emerald"/>
            <x-molecules.card title="Total Nilai Aset" description="Berdasarkan harga beli" total="Rp {{ number_format($totalAssetValue, 0, ',', '.') }}" color="emerald"/>
            <x-molecules.card title="Peringatan Stok Menipis" description="Berdasarkan harga beli" total="{{ $lowStockCount }} Item" color="red"/>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk / SKU..."
                               class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- Category Filter -->
                    <div class="w-full sm:w-48">
                        <select wire:model.live="categoryFilter" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Stock Status Filter -->
                    <div class="w-full sm:w-48">
                        <select wire:model.live="statusFilter" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Semua Status Stok</option>
                            <option value="low">Hanya Stok Menipis</option>
                            <option value="safe">Hanya Stok Aman</option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ $products->count() }}</span> data
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">SKU</th>
                            <th class="px-4 py-3.5">Nama Produk</th>
                            <th class="px-4 py-3.5">Kategori / Supplier</th>
                            <th class="px-4 py-3.5 text-right">Harga Beli</th>
                            <th class="px-4 py-3.5 text-center">Batas Min.</th>
                            <th class="px-4 py-3.5 text-center">Stok Fisik</th>
                            <th class="px-4 py-3.5 text-right">Valuasi Aset</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($products as $p)
                        @php $asset = $p->purchase_price * $p->current_stock; @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 font-mono text-xs text-blue-600 dark:text-blue-400 font-semibold whitespace-nowrap">
                                {{ $p->sku }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">
                                {{ $p->name }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $p->category->name ?? '-' }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $p->supplier->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs text-gray-600 dark:text-gray-300">
                                Rp {{ number_format($p->purchase_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono text-xs text-gray-500">
                                {{ $p->minimum_stock }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-sm {{ $p->is_low_stock ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                {{ $p->current_stock }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs font-bold text-gray-900 dark:text-white">
                                Rp {{ number_format($asset, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($p->is_low_stock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        Aman
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                Tidak ada data stok produk yang memenuhi kriteria filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($products->count() > 0)
                    <tfoot class="bg-gray-50 dark:bg-gray-800/80 font-semibold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700">
                        <tr>
                            <td colspan="5" class="px-4 py-3.5 text-right text-xs uppercase tracking-wider">Total Aset Keseluruhan:</td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold">{{ $totalUnits }} Unit</td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-emerald-600">
                                Rp {{ number_format($totalAssetValue, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
</div>
