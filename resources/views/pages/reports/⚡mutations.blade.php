<?php

use App\Services\StockTransactionService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Laporan Mutasi Stok - Stockify')] class extends Component
{
    public string $startDate = '';
    public string $endDate = '';
    public string $typeFilter = '';
    public string $search = '';

    public function mount(): void
    {
        $this->startDate = now()->subDays(30)->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function with(StockTransactionService $stockService): array
    {
        $transactions = $stockService->getTransactionsByDateRange($this->startDate, $this->endDate);

        if (!empty($this->typeFilter)) {
            $transactions = $transactions->where('type', $this->typeFilter);
        }

        if (!empty($this->search)) {
            $transactions = $transactions->filter(function($tx) {
                $p = $tx->product;
                if (!$p) return false;
                return str_contains(strtolower($p->name), strtolower($this->search)) ||
                       str_contains(strtolower($p->sku), strtolower($this->search));
            });
        }

        // Summary calculations
        $totalMutations = $transactions->count();
        $totalIn = $transactions->where('type', 'in')->sum('quantity');
        $totalOut = $transactions->where('type', 'out')->sum('quantity');
        $totalAdjustment = $transactions->where('type', 'adjustment')->sum('quantity');

        return [
            'transactions' => $transactions,
            'totalMutations' => $totalMutations,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'totalAdjustment' => $totalAdjustment,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header with Export Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Mutasi & Pergerakan Stok</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Audit arus keluar masuk barang, log transaksi, dan penyesuaian fisik berdasarkan rentang waktu.
                </p>
            </div>

            <!-- Export Actions -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.mutations.csv', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter]) }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel (CSV)
                </a>

                <a href="{{ route('reports.mutations.print', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter]) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-500/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak / Simpan PDF
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Mutasi</p>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalMutations }}</h3>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Aktivitas pergerakan stok</p>
            </div>

            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barang Masuk (+)</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">+{{ number_format($totalIn, 0, ',', '.') }}</h3>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Unit diterima gudang</p>
            </div>

            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barang Keluar (-)</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-1">-{{ number_format($totalOut, 0, ',', '.') }}</h3>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Unit dikeluarkan</p>
            </div>

            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Penyesuaian Opname</p>
                <h3 class="text-2xl font-bold {{ $totalAdjustment >= 0 ? 'text-blue-600' : 'text-red-600' }} mt-1">
                    {{ $totalAdjustment > 0 ? '+' : '' }}{{ number_format($totalAdjustment, 0, ',', '.') }}
                </h3>
                <p class="text-xs text-gray-500 mt-1">Selisih penyesuaian</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Start Date -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input wire:model.live="startDate" type="date"
                           class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input wire:model.live="endDate" type="date"
                           class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Tipe Mutasi</label>
                    <select wire:model.live="typeFilter" class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Semua Tipe</option>
                        <option value="in">Barang Masuk (In)</option>
                        <option value="out">Barang Keluar (Out)</option>
                        <option value="adjustment">Stock Opname</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Produk / SKU</label>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Ketik SKU atau nama..."
                           class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Mutations Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal</th>
                            <th class="px-4 py-3.5">SKU</th>
                            <th class="px-4 py-3.5">Nama Produk</th>
                            <th class="px-4 py-3.5 text-center">Tipe</th>
                            <th class="px-4 py-3.5 text-center">Kuantitas</th>
                            <th class="px-4 py-3.5">Petugas</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($transactions as $tx)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 text-xs font-mono text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $tx->date ? $tx->date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-blue-600 dark:text-blue-400 font-semibold whitespace-nowrap">
                                {{ $tx->product->sku ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">
                                {{ $tx->product->name ?? 'Produk Dihapus' }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($tx->type === 'in')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Masuk
                                    </span>
                                @elseif($tx->type === 'out')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Keluar
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        Opname
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-xs whitespace-nowrap">
                                <span class="{{ $tx->type === 'in' ? 'text-emerald-600' : ($tx->type === 'out' ? 'text-amber-600' : 'text-blue-600') }}">
                                    {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : ($tx->quantity > 0 ? '+' : '')) }}{{ $tx->quantity }} Unit
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $tx->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($tx->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Selesai
                                    </span>
                                @elseif($tx->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        Pending
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        Batal
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                {{ $tx->notes ?: '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                Tidak ada catatan mutasi stok pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
