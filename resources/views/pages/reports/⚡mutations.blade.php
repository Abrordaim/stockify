<?php

use App\Services\CategoryService;
use App\Services\StockTransactionService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Laporan Mutasi Stok - Stockify')] class extends Component
{
    public string $startDate = '';
    public string $endDate = '';
    public string $period = '30_days';
    public string $typeFilter = '';
    public string $statusFilter = '';
    public string $categoryId = '';
    public bool $myPendingOnly = false;
    public string $search = '';

    public function mount(): void
    {
        $this->setPeriod('30_days');
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $today = now()->toDateString();

        match ($period) {
            'today' => [$this->startDate = $today, $this->endDate = $today],
            '7_days' => [$this->startDate = now()->subDays(6)->toDateString(), $this->endDate = $today],
            '30_days' => [$this->startDate = now()->subDays(29)->toDateString(), $this->endDate = $today],
            'this_month' => [$this->startDate = now()->startOfMonth()->toDateString(), $this->endDate = now()->endOfMonth()->toDateString()],
            default => null,
        };
    }

    public function with(StockTransactionService $stockService, CategoryService $categoryService): array
    {
        $categories = $categoryService->getAllCategories();
        $transactions = $stockService->getTransactionsByDateRange($this->startDate, $this->endDate);

        // Filter Type (in, out, adjustment)
        if (!empty($this->typeFilter)) {
            $transactions = $transactions->where('type', $this->typeFilter);
        }

        // Filter Status (Pending, Diterima, Ditolak, Dikeluarkan)
        if (!empty($this->statusFilter)) {
            $transactions = $transactions->filter(function ($tx) {
                if ($this->statusFilter === 'Diterima') {
                    return in_array($tx->status, ['Diterima', 'completed']) && $tx->type !== 'out';
                }
                if ($this->statusFilter === 'Dikeluarkan') {
                    return in_array($tx->status, ['Dikeluarkan', 'completed']) && $tx->type === 'out';
                }
                if ($this->statusFilter === 'Pending') {
                    return in_array($tx->status, ['Pending', 'pending']);
                }
                if ($this->statusFilter === 'Ditolak') {
                    return in_array($tx->status, ['Ditolak', 'cancelled']);
                }
                return $tx->status === $this->statusFilter;
            });
        }

        // Filter Category
        if (!empty($this->categoryId)) {
            $transactions = $transactions->filter(function ($tx) {
                return $tx->product && (string) $tx->product->category_id === (string) $this->categoryId;
            });
        }

        // Filter "Menunggu konfirmasi saya" (khusus staff)
        if ($this->myPendingOnly) {
            $transactions = $transactions->filter(function ($tx) {
                return in_array($tx->status, ['Pending', 'pending']);
            });
        }

        // Filter Search
        if (!empty($this->search)) {
            $transactions = $transactions->filter(function ($tx) {
                $p = $tx->product;
                if (!$p) return false;
                return str_contains(strtolower($p->name), strtolower($this->search)) ||
                       str_contains(strtolower($p->sku), strtolower($this->search));
            });
        }

        // Summary calculations: ONLY count confirmed (Diterima / Dikeluarkan)
        $totalIn = (int) $transactions->where('type', 'in')
            ->filter(fn($tx) => in_array($tx->status, ['Diterima', 'completed']))
            ->sum('quantity');

        $totalOut = (int) $transactions->where('type', 'out')
            ->filter(fn($tx) => in_array($tx->status, ['Dikeluarkan', 'completed']))
            ->sum('quantity');

        $netFlux = $totalIn - $totalOut;

        $pendingCount = $transactions->filter(fn($tx) => in_array($tx->status, ['Pending', 'pending']))->count();
        $totalMutations = $transactions->count();

        return [
            'categories' => $categories,
            'transactions' => $transactions,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netFlux' => $netFlux,
            'pendingCount' => $pendingCount,
            'totalMutations' => $totalMutations,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header with Export Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">

                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Mutasi & Pergerakan Stok</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Audit arus keluar masuk barang, verifikasi dua tahap, riwayat stok sebelum/sesudah, dan filter laporan.
                </p>
            </div>

            <!-- Export Actions -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.mutations.csv', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter, 'status' => $statusFilter, 'category_id' => $categoryId]) }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel (CSV)
                </a>

                <a href="{{ route('reports.mutations.print', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter, 'status' => $statusFilter, 'category_id' => $categoryId]) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-500/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak / Simpan PDF
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards (Hitung HANYA Diterima / Dikeluarkan) -->
        <div class="grid grid-cols-2  lg:grid-cols-4 gap-4">
            <!-- Total Barang Masuk (Diterima) -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total Barang Masuk</p>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Diterima</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">+{{ number_format($totalIn, 0, ',', '.') }} <span class="text-xs font-normal text-gray-400">Unit</span></h3>
                <p class="text-xs text-gray-500 mt-1">Hanya dari mutasi yang telah diverifikasi</p>
            </div>

            <!-- Total Barang Keluar (Dikeluarkan) -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Total Barang Keluar</p>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-50 text-blue-700 border border-blue-200">Dikeluarkan</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">-{{ number_format($totalOut, 0, ',', '.') }} <span class="text-xs font-normal text-gray-400">Unit</span></h3>
                <p class="text-xs text-gray-500 mt-1">Hanya dari mutasi yang telah diserahkan</p>
            </div>

            <!-- Net Arus Barang -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Arus Barang</p>
                <h3 class="text-2xl font-bold {{ $netFlux >= 0 ? 'text-emerald-600' : 'text-amber-600' }} mt-2">
                    {{ $netFlux > 0 ? '+' : '' }}{{ number_format($netFlux, 0, ',', '.') }} <span class="text-xs font-normal text-gray-400">Unit</span>
                </h3>
                <p class="text-xs text-gray-500 mt-1">Selisih arus fisik periode ini</p>
            </div>

            <!-- Menunggu Konfirmasi -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Menunggu Konfirmasi</p>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                </div>
                <h3 class="text-2xl font-bold text-amber-600 mt-2">{{ $pendingCount }} <span class="text-xs font-normal text-gray-400">Transaksi</span></h3>
                <p class="text-xs text-amber-600/80 mt-1">Belum mempengaruhi stok sistem</p>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
            <!-- Period Quick Switcher -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Pilihan Cepat Periode:</span>
                    <div class="inline-flex rounded-xl bg-gray-100 dark:bg-gray-700/60 p-1">
                        <button wire:click="setPeriod('today')" type="button"
                                class="px-3 py-1 text-xs font-medium rounded-lg transition {{ $period === 'today' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                            Hari Ini
                        </button>
                        <button wire:click="setPeriod('7_days')" type="button"
                                class="px-3 py-1 text-xs font-medium rounded-lg transition {{ $period === '7_days' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                            7 Hari
                        </button>
                        <button wire:click="setPeriod('30_days')" type="button"
                                class="px-3 py-1 text-xs font-medium rounded-lg transition {{ $period === '30_days' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                            30 Hari
                        </button>
                        <button wire:click="setPeriod('this_month')" type="button"
                                class="px-3 py-1 text-xs font-medium rounded-lg transition {{ $period === 'this_month' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                            Bulan Ini
                        </button>
                    </div>
                </div>

                @if(auth()->user()->isStaff())
                <!-- Staff specific filter: Menunggu Konfirmasi Saya -->
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input wire:model.live="myPendingOnly" type="checkbox" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500"></div>
                        <span class="ml-2 text-xs font-semibold text-amber-700 dark:text-amber-400">
                            ⚡ Menunggu konfirmasi saya
                        </span>
                    </label>
                </div>
                @endif
            </div>

            <!-- Filter Inputs Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
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

                <!-- Status Filter (4 Status Resmi) -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Status Mutasi</label>
                    <select wire:model.live="statusFilter" class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="Pending">Pending (Menunggu)</option>
                        <option value="Diterima">Diterima (Masuk)</option>
                        <option value="Dikeluarkan">Dikeluarkan (Keluar)</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Kategori Produk</label>
                    <select wire:model.live="categoryId" class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search SKU / Nama -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Produk / SKU</label>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Ketik SKU atau nama..."
                           class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Table Utama Ringkas dengan Expandable Row -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Daftar Riwayat Transaksi Mutasi</h3>
                    <p class="text-xs text-gray-400">Klik baris tabel untuk melihat rincian petugas pencatat, pengonfirmasi, dan audit stok.</p>
                </div>
                <span class="text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                    {{ $transactions->count() }} Data Ditemukan
                </span>
            </div>

            <div class="overflow-x-auto scroll-auto ">
                <table class=" table-auto w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class=" px-2 py-3.5 text-center">Tanggal</th>
                            <th class=" px-2 py-3.5 text-center">SKU</th>
                            <th class=" px-2 py-3.5 text-center">Nama Produk</th>
                            <th class=" px-2 py-3.5 text-center">Tipe</th>
                            <th class=" px-2 py-3.5 text-center">Kuantitas</th>
                            <th class=" px-2 py-3.5 text-center">Status</th>
                            <th class=" px-2 py-3.5 text-center">Stok Sesudah</th>
                            <th class=" px-2 py-3.5 text-center">Dicatat oleh</th>
                            <th class=" px-2 py-3.5 text-center">Dikonfirmasi oleh</th>
                            <th class=" px-2 py-3.5 text-center">Waktu konfirmasi</th>
                            <th class=" px-2 py-3.5 text-center">Perubahan Stock fisik</th>

                            {{-- <th class="border  py-3.5 text-center w-12">Detail</th> --}}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($transactions as $tx)
                        @php
                            $statusNormalized = match ($tx->status) {
                                'completed' => ($tx->type === 'out' ? 'Dikeluarkan' : 'Diterima'),
                                'cancelled' => 'Ditolak',
                                'pending' => 'Pending',
                                default => $tx->status,
                            };
                        @endphp
                        {{-- <tr x-data="{ expanded: false }" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td colspan="1" class="p-0">
                                <!-- MAIN ROW SUMMARY (Click to Toggle Detail) -->
                                <div @click="expanded = !expanded" class="border cursor-pointer grid grid-cols-12 items-center px-4 py-3.5 gap-2 select-none">
                                    <!-- Tanggal -->
                                    <div class=" border col-span-2 text-xs font-mono text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $tx->date ? $tx->date->format('d/m/Y') : '-' }}
                                    </div>

                                    <!-- SKU -->
                                    <div class="border col-span-2  font-mono text-xs text-blue-600 dark:text-blue-400 font-semibold truncate">
                                        {{ $tx->product->sku ?? '-' }}
                                    </div>

                                    <!-- Nama Produk -->
                                    <div class="col-span-3 font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $tx->product->name ?? 'Produk Dihapus' }}
                                    </div>

                                    <!-- Tipe -->
                                    <div class="col-span-1 text-center whitespace-nowrap">
                                        @if($tx->type === 'in')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                Masuk
                                            </span>
                                        @elseif($tx->type === 'out')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                Keluar
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                Opname
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Kuantitas -->
                                    <div class="col-span-1 text-center font-mono font-bold text-xs whitespace-nowrap">
                                        <span class="{{ $tx->type === 'in' ? 'text-emerald-600' : ($tx->type === 'out' ? 'text-blue-600' : 'text-purple-600') }}">
                                            {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : ($tx->quantity > 0 ? '+' : '')) }}{{ $tx->quantity }}
                                        </span>
                                    </div>

                                    <!-- Status (4 Nilai Resmi) -->
                                    <div class="col-span-1 text-center whitespace-nowrap">
                                        @if($statusNormalized === 'Pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1 animate-pulse"></span>
                                                Pending
                                            </span>
                                        @elseif($statusNormalized === 'Diterima')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                                Diterima
                                            </span>
                                        @elseif($statusNormalized === 'Dikeluarkan')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1"></span>
                                                Dikeluarkan
                                            </span>
                                        @elseif($statusNormalized === 'Ditolak')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                                {{ $statusNormalized }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Stok Sesudah -->
                                    <div class="col-span-1 text-right font-mono font-bold text-xs text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ number_format($tx->stock_after) }} Unit
                                    </div>

                                    <!-- Chevron Button -->
                                    <div class="col-span-1 text-center">
                                        <button type="button" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition transform" :class="expanded ? 'rotate-180 text-blue-600' : ''">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- EXPANDABLE DETAIL ACCORDION (Revealed on Click) -->
                                <div x-show="expanded" x-collapse class="bg-gray-50/90 dark:bg-gray-800/90 border-t border-b border-gray-200/80 dark:border-gray-700/80 p-4 transition">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                                        <!-- Dicatat oleh -->
                                        <div class="bg-white dark:bg-gray-700 p-3 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm space-y-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Dicatat Oleh</span>
                                            <p class="font-bold text-gray-900 dark:text-white text-sm">
                                                {{ $tx->createdBy->name ?? ($tx->user->name ?? '-') }}
                                            </p>
                                            <span class="inline-block px-2 py-0.5 text-[10px] rounded font-semibold bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200 capitalize">
                                                Peran: {{ $tx->createdBy->role ?? ($tx->user->role ?? 'User') }}
                                            </span>
                                        </div>

                                        <!-- Dikonfirmasi oleh -->
                                        <div class="bg-white dark:bg-gray-700 p-3 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm space-y-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Dikonfirmasi Oleh</span>
                                            @if($tx->confirmedBy)
                                                <p class="font-bold text-gray-900 dark:text-white text-sm">
                                                    {{ $tx->confirmedBy->name }}
                                                </p>
                                                <span class="inline-block px-2 py-0.5 text-[10px] rounded font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    Staff: {{ $tx->confirmedBy->role }}
                                                </span>
                                            @elseif(in_array($statusNormalized, ['Pending']))
                                                <p class="font-bold text-amber-600 text-sm flex items-center space-x-1">
                                                    <span>Menunggu Konfirmasi</span>
                                                </p>
                                                <span class="text-[11px] text-gray-400">Belum diverifikasi staff gudang</span>
                                            @else
                                                <p class="text-gray-400 text-sm">-</p>
                                            @endif
                                        </div>

                                        <!-- Waktu Konfirmasi & Mutasi Stok Sebelum -> Sesudah -->
                                        <div class="bg-white dark:bg-gray-700 p-3 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm space-y-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Waktu Konfirmasi</span>
                                            <p class="font-bold text-gray-900 dark:text-white">
                                                {{ $tx->confirmed_at ? $tx->confirmed_at->format('d/m/Y H:i') : '-' }}
                                            </p>
                                            @if($tx->confirmed_at)
                                                <span class="text-[11px] text-gray-400 block">{{ $tx->confirmed_at->diffForHumans() }}</span>
                                            @else
                                                <span class="text-[11px] text-amber-500 font-medium">Pending Verifikasi Fisik</span>
                                            @endif
                                        </div>

                                        <!-- Jejak Perubahan Stok (Sebelum -> Sesudah) -->
                                        <div class="bg-white dark:bg-gray-700 p-3 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm space-y-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Perubahan Stok Fisik</span>
                                            <div class="flex items-center space-x-2 mt-0.5">
                                                <span class="font-mono text-sm font-bold text-gray-600 dark:text-gray-300">{{ $tx->stock_before }} Unit</span>
                                                <span class="text-gray-400">→</span>
                                                <span class="font-mono text-sm font-bold {{ $statusNormalized === 'Pending' || $statusNormalized === 'Ditolak' ? 'text-gray-500' : 'text-blue-600 dark:text-blue-400' }}">
                                                    {{ $tx->stock_after }} Unit
                                                </span>
                                            </div>
                                            @if($statusNormalized === 'Pending' || $statusNormalized === 'Ditolak')
                                                <span class="text-[11px] text-amber-600 font-medium block">(Stok belum berubah)</span>
                                            @else
                                                <span class="text-[11px] text-emerald-600 font-medium block">✓ Stok sistem telah disesuaikan</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Catatan Tambahan -->
                                    <div class="mt-3 bg-white dark:bg-gray-700/60 p-3 rounded-xl border border-gray-100 dark:border-gray-600 flex items-start space-x-2">
                                        <span class="text-gray-400 font-semibold text-xs">Catatan Transaksi:</span>
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $tx->notes ?: 'Tidak ada catatan tambahan.' }}</span>
                                    </div>
                                </div>
                            </td>
                        </tr> --}}
                        <tr   class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <div>
                                <div>
                                    <td class="p-2 text-xs font-mono text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $tx->date ? $tx->date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="p-2 font-mono text-xs text-blue-600 dark:text-blue-400 font-semibold truncate">
                                        {{ $tx->product->sku ?? '-' }}
                                    </td>
                                    <td class="p-2 font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $tx->product->name ?? 'Produk Dihapus' }}
                                    </td>
                                    <td class="text-center whitespace-nowrap p-2">
                                        @if($tx->type === 'in')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    Masuk
                                                </span>
                                            @elseif($tx->type === 'out')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                    Keluar
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                    Opname
                                                </span>
                                            @endif
                                    </td>
                                    <td class="text-center font-mono font-bold text-xs whitespace-nowrap">
                                        <span class="{{ $tx->type === 'in' ? 'text-emerald-600' : ($tx->type === 'out' ? 'text-blue-600' : 'text-purple-600') }}">
                                            {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : ($tx->quantity > 0 ? '+' : '')) }}{{ $tx->quantity }}
                                        </span>
                                    </td>
                                    <td class="text-center whitespace-nowrap p-2">
                                        @if($statusNormalized === 'Pending')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1 animate-pulse"></span>
                                                    Pending
                                                </span>
                                            @elseif($statusNormalized === 'Diterima')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                                    Diterima
                                                </span>
                                            @elseif($statusNormalized === 'Dikeluarkan')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1"></span>
                                                    Dikeluarkan
                                                </span>
                                            @elseif($statusNormalized === 'Ditolak')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>
                                                    Ditolak
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                                    {{ $statusNormalized }}
                                                </span>
                                            @endif
                                    </td>
                                    <td class="text-center font-mono font-bold text-xs text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ number_format($tx->stock_after) }} Unit
                                    </td>
                                    <td class="px-4 ">
                                        <p class="font-medium text-center text-gray-900 dark:text-white text-sm">{{ $tx->createdBy->name ?? ($tx->user->name ?? '-') }}
                                            {{-- <span class="font-normal">{{ $tx->createdBy->role ?? ($tx->user->role ?? 'User') }}</span> --}}
                                        </p>

                                    </td>
                                    <td class="px-2 font-medium text-center text-sm text-gray-900 ">
                                        @if($tx->confirmedBy)
                                                <p class="font-bold text-gray-900 dark:text-white text-sm">
                                                    {{ $tx->confirmedBy->name }}
                                                </p>
                                                <span class="inline-block px-2 py-0.5 text-[10px] rounded font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    Staff: {{ $tx->confirmedBy->role }}
                                                </span>
                                            @elseif(in_array($statusNormalized, ['Pending']))
                                                <p class="font-bold text-amber-600 text-sm flex items-center space-x-1">
                                                    <span>Menunggu Konfirmasi</span>
                                                </p>
                                                <span class="text-[11px] text-gray-400">Belum diverifikasi staff gudang</span>
                                            @else
                                                <p class="text-gray-400 text-sm">-</p>
                                            @endif
                                    </td>
                                    <td class="p-2 text-xs font-mono text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $tx->confirmed_at ? $tx->confirmed_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="text-center font-mono font-bold text-xs">
                                        <span class="text-gray-900">{{ $tx->stock_before }}</span> -> <span class="text-gray-900">{{ $tx->stock_after }}</span>
                                    </td>
                                </div>
                            </div>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Tidak ada catatan mutasi stok pada filter periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
