<?php

use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\StockTransactionService;
use App\Services\UserService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Dashboard - Stockify')] class extends Component
{
    /**
     * Compute data for dashboard view using Services.
     */
    public function with(
        ProductService $productService,
        StockTransactionService $stockService,
        UserService $userService
    ): array {
        $user = auth()->user();

        $totalProducts = $productService->countProducts();
        $lowStockProducts = $productService->getLowStockProducts();
        $transactionSummary = $stockService->getDashboardSummary();
        $recentTransactions = $stockService->getRecentTransactions(6);
        $totalUsers = $userService->countUsers();

        return [
            'user' => $user,
            'totalProducts' => $totalProducts,
            'lowStockProducts' => $lowStockProducts,
            'transactionSummary' => $transactionSummary,
            'recentTransactions' => $recentTransactions,
            'totalUsers' => $totalUsers,
        ];
    }

    public function logout(): void
    {
        /** @var AuthService $authService */
        $authService = app(AuthService::class);
        $authService->logout();
        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Welcome Banner Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Halo, {{ $user->name }}! 👋
                    </h1>
                    @if($user->isAdmin())
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300">
                            Administrator
                        </span>
                    @elseif($user->isManager())
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                            Manajer Gudang
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                            Staff Gudang
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Berikut adalah ringkasan inventaris dan operasional gudang hari ini.
                </p>
            </div>
            <div class="mt-4 md:mt-0 text-sm text-gray-500 dark:text-gray-400 font-medium">
                {{ now()->translatedFormat('l, d F Y') }}
            </div>
        </div>

        <!-- STATS CARDS (DYNAMIC ACCORDING TO ROLE) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Produk (Semua Role) -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Produk</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalProducts }}</h3>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Item terdaftar</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>

            <!-- Card 2: Stok Menipis (Alert / Low Stock) -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stok Menipis</p>
                    <h3 class="text-2xl font-bold {{ $lowStockProducts->count() > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }} mt-1">
                        {{ $lowStockProducts->count() }}
                    </h3>
                    <p class="text-xs {{ $lowStockProducts->count() > 0 ? 'text-red-500 font-medium' : 'text-emerald-500' }} mt-1">
                        {{ $lowStockProducts->count() > 0 ? 'Perlu Restock Segera' : 'Semua Stok Aman' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <!-- Card 3: Barang Masuk Hari Ini -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barang Masuk Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $transactionSummary['today_in'] ?? 0 }}</h3>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Transaksi masuk</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
            </div>

            <!-- Card 4 (Role Specific): Admin (Users), Manager (Pending / Out), Staff (Pending Tasks) -->
            @if($user->isAdmin())
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Pengguna</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalUsers }}</h3>
                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">Akun aktif</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            @elseif($user->isManager())
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barang Keluar Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $transactionSummary['today_out'] ?? 0 }}</h3>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Transaksi keluar</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
            </div>
            @else
            <!-- Staff: Antrean Tugas Pending -->
            <div class="p-5 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tugas Menunggu</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $transactionSummary['pending_count'] ?? 0 }}</h3>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Perlu pemeriksaan fisik</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            @endif
        </div>

        <!-- MAIN CONTENT GRIDS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (2 Cols): Stok Menipis (Alert Table) -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        <h2 class="font-bold text-gray-900 dark:text-white">Peringatan Stok Menipis (Low Stock Alert)</h2>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $lowStockProducts->count() }} Produk
                    </span>
                </div>

                @if($lowStockProducts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2.5">Produk</th>
                                <th class="px-3 py-2.5">SKU</th>
                                <th class="px-3 py-2.5 text-center">Stok Saat Ini</th>
                                <th class="px-3 py-2.5 text-center">Min. Stok</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($lowStockProducts as $p)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                                <td class="px-3 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $p->name }}
                                    <span class="block text-xs text-gray-400">{{ $p->category->name ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-3 text-xs font-mono">{{ $p->sku }}</td>
                                <td class="px-3 py-3 text-center font-bold text-red-600">
                                    {{ $p->current_stock }}
                                </td>
                                <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-300">
                                    {{ $p->minimum_stock }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        Menipis
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-400 text-sm">
                    <svg class="w-12 h-12 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Semua stok produk saat ini dalam batas aman!
                </div>
                @endif
            </div>

            <!-- Right Column (1 Col): Riwayat Transaksi Terbaru -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h2 class="font-bold text-gray-900 dark:text-white">Aktivitas Terakhir</h2>
                    <span class="text-xs text-blue-600 hover:underline cursor-pointer">Lihat Semua</span>
                </div>

                <div class="space-y-3">
                    @forelse($recentTransactions as $tx)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $tx->type === 'in' ? 'bg-emerald-100 text-emerald-700' : ($tx->type === 'out' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                                @if($tx->type === 'in')
                                    ↓
                                @elseif($tx->type === 'out')
                                    ↑
                                @else
                                    ↕
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $tx->product->name ?? 'Produk' }}
                                </p>
                                <p class="text-[11px] text-gray-400">
                                    {{ $tx->user->name ?? 'User' }} • {{ $tx->date ? $tx->date->format('d/m') : '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold {{ $tx->type === 'in' ? 'text-emerald-600' : ($tx->type === 'out' ? 'text-amber-600' : 'text-blue-600') }}">
                                {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : '') }}{{ $tx->quantity }}
                            </span>
                            <span class="block text-[10px] text-gray-400 capitalize">{{ $tx->status }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-xs text-gray-400 py-6">Belum ada aktivitas transaksi.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
