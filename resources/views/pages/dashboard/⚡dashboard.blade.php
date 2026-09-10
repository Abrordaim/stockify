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
    public string $period = '7_days';

    public function mount(): void
    {
        if (auth()->check() && auth()->user()->isStaff()) {
            $this->redirect(route('stock.tasks'), navigate: true);
        }
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;

        // Dispatch event for ApexCharts to smoothly update series
        /** @var StockTransactionService $stockService */
        $stockService = app(StockTransactionService::class);
        $chartData = $stockService->getChartDataByPeriod($this->period);

        $this->dispatch('chart-updated', [
            'categories' => $chartData['categories'],
            'inSeries' => $chartData['inSeries'],
            'outSeries' => $chartData['outSeries'],
        ]);
    }

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
        $periodStats = $stockService->getTransactionStatsByPeriod($this->period);
        $chartData = $stockService->getChartDataByPeriod($this->period);
        $recentTransactions = $stockService->getRecentTransactions(6);
        $userActivities = $stockService->getRecentUserActivities(8);
        $totalUsers = $userService->countUsers();

        return [
            'user' => $user,
            'totalProducts' => $totalProducts,
            'lowStockProducts' => $lowStockProducts,
            'transactionSummary' => $transactionSummary,
            'periodStats' => $periodStats,
            'chartData' => $chartData,
            'recentTransactions' => $recentTransactions,
            'userActivities' => $userActivities,
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

        <!-- Welcome Banner Header + Period Filter -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
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
                    Berikut adalah ringkasan inventaris, tren mutasi stok, dan aktivitas operasional gudang.
                </p>
            </div>

            <!-- Interactive Quick Period Filter Bar -->
            <div class="flex flex-wrap items-center gap-1.5 p-1 bg-gray-100 dark:bg-gray-700/60 rounded-xl">
                <button wire:click="setPeriod('today')" type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $period === 'today' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                    Hari Ini
                </button>
                <button wire:click="setPeriod('7_days')" type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $period === '7_days' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                    7 Hari
                </button>
                <button wire:click="setPeriod('30_days')" type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $period === '30_days' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                    30 Hari
                </button>
                <button wire:click="setPeriod('this_month')" type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $period === 'this_month' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                    Bulan Ini
                </button>
                <button wire:click="setPeriod('this_year')" type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $period === 'this_year' ? 'bg-white text-blue-600 shadow-sm font-bold dark:bg-gray-800 dark:text-blue-400' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300' }}">
                    Tahun Ini
                </button>
            </div>
        </div>

        <!-- STATS CARDS ROW -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Produk -->
            <x-molecules.card title="Total Produk" description="Item aktif di katalog" total="{{ $totalProducts }}" color="blue">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </x-molecules.card>

            <!-- Card 2: Stok Menipis (Alert / Low Stock) -->
            <x-molecules.card title="stock menipis" description="Perlu Restock Segera" total="{{  $lowStockProducts->count() }}" color="red">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </x-molecules.card>

            <!-- Card 3: Barang Masuk (Periode Terpilih) -->
            <x-molecules.card title="Barang masuk" description="{{ $periodStats['period_label'] }}" total="+{{ number_format($periodStats['in_qty']) }}" color="emerald">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
            </x-molecules.card>

            <!-- Card 4: Barang Keluar (Periode Terpilih) -->
            <x-molecules.card title="barang keluar" color="amber" total="-{{ number_format($periodStats['out_qty']) }}" description="{{  $periodStats['period_label'] }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </x-molecules.card>
        </div>

        <!-- MIDDLE ROW: CHART & LOW STOCK ALERT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (2 Cols): GRAFIK STOK BARANG (ApexCharts) -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            <h2 class="font-bold text-gray-900 dark:text-white">Grafik Mutasi Stok Barang</h2>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Tren perbandingan volume barang masuk vs barang keluar ({{ $periodStats['period_label'] }})
                        </p>
                    </div>

                    <!-- Chart Legend Badges -->
                    <div class="flex items-center space-x-3 text-xs">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                            Masuk: +{{ number_format($chartData['totalIn']) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5"></span>
                            Keluar: -{{ number_format($chartData['totalOut']) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-semibold">
                            Net: {{ $periodStats['net_qty'] > 0 ? '+' : '' }}{{ number_format($periodStats['net_qty']) }}
                        </span>
                    </div>
                </div>

                <!-- ApexCharts Interactive Container -->
                <div wire:ignore
                     x-data="{
                         chart: null,
                         categories: @js($chartData['categories']),
                         inSeries: @js($chartData['inSeries']),
                         outSeries: @js($chartData['outSeries']),
                         initChart() {
                             const options = {
                                 chart: {
                                     type: 'area',
                                     height: 290,
                                     toolbar: { show: false },
                                     fontFamily: 'inherit',
                                     animations: { enabled: true, easing: 'easeinout', speed: 400 }
                                 },
                                 series: [
                                     { name: 'Barang Masuk', data: this.inSeries },
                                     { name: 'Barang Keluar', data: this.outSeries }
                                 ],
                                 colors: ['#10b981', '#f59e0b'],
                                 fill: {
                                     type: 'gradient',
                                     gradient: {
                                         shadeIntensity: 1,
                                         opacityFrom: 0.45,
                                         opacityTo: 0.05,
                                         stops: [0, 90, 100]
                                     }
                                 },
                                 dataLabels: { enabled: false },
                                 stroke: { curve: 'smooth', width: 2.5 },
                                 xaxis: {
                                     categories: this.categories,
                                     labels: {
                                         style: { colors: '#9ca3af', fontSize: '11px' }
                                     },
                                     axisBorder: { show: false },
                                     axisTicks: { show: false }
                                 },
                                 yaxis: {
                                     labels: {
                                         style: { colors: '#9ca3af', fontSize: '11px' },
                                         formatter: (val) => Math.round(val) + ' Unit'
                                     }
                                 },
                                 tooltip: {
                                     y: { formatter: (val) => val + ' Unit' },
                                     theme: 'light'
                                 },
                                 grid: {
                                     borderColor: '#f3f4f6',
                                     strokeDashArray: 4
                                 }
                             };

                             this.chart = new ApexCharts(this.$refs.chartContainer, options);
                             this.chart.render();
                         },
                         update(data) {
                             if (!this.chart) return;
                             const newCategories = data[0] ? data[0].categories : data.categories;
                             const newInSeries = data[0] ? data[0].inSeries : data.inSeries;
                             const newOutSeries = data[0] ? data[0].outSeries : data.outSeries;

                             this.chart.updateOptions({
                                 xaxis: { categories: newCategories }
                             });
                             this.chart.updateSeries([
                                 { name: 'Barang Masuk', data: newInSeries },
                                 { name: 'Barang Keluar', data: newOutSeries }
                             ]);
                         }
                     }"
                     x-init="initChart()"
                     @chart-updated.window="update($event.detail)"
                     class="w-full">
                    <div x-ref="chartContainer" class="w-full h-72"></div>
                </div>
            </div>

            <!-- Right Column (1 Col): Peringatan Stok Menipis -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            <h2 class="font-bold text-gray-900 dark:text-white">Peringatan Stok Menipis</h2>
                        </div>
                        <span class="text-xs text-red-600 font-semibold">
                            {{ $lowStockProducts->count() }} Produk
                        </span>
                    </div>

                    @if($lowStockProducts->count() > 0)
                    <div class="space-y-3 mt-3 max-h-64 overflow-y-auto pr-1">
                        @foreach($lowStockProducts as $p)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-red-50/60 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40">
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $p->name }}</p>
                                <span class="text-[10px] font-mono text-gray-400">{{ $p->sku }} • Min: {{ $p->minimum_stock }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-xs font-bold text-red-600 dark:text-red-400 block">
                                    {{ $p->current_stock }} Unit
                                </span>
                                <span class="text-[9px] uppercase font-bold text-red-500">Kritis</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12 text-gray-400 text-xs">
                        <svg class="w-10 h-10 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Semua stok berada dalam batas aman!
                    </div>
                    @endif
                </div>

                <a href="{{ route('stock.in') }}" class="block w-full py-2.5 px-4 text-center text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">
                    + Buat Penerimaan Barang Baru
                </a>
            </div>
        </div>

        <!-- BOTTOM ROW: RECENT TRANSACTIONS & USER ACTIVITY AUDIT FEED -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left (2 Cols): Riwayat Transaksi Lengkap -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">Transaksi Terkini</h2>
                        <p class="text-xs text-gray-400">Mutasi barang terakhir yang tercatat di sistem gudang</p>
                    </div>
                    <a href="{{ route('reports.mutations') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Lihat Semua Mutasi →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2.5">Produk</th>
                                <th class="px-3 py-2.5 text-center">Tipe</th>
                                <th class="px-3 py-2.5 text-center">Kuantitas</th>
                                <th class="px-3 py-2.5">Petugas</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($recentTransactions as $tx)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                                <td class="px-3 py-3">
                                    <span class="font-medium text-gray-900 dark:text-white block">{{ $tx->product->name ?? 'Produk' }}</span>
                                    <span class="text-[11px] font-mono text-gray-400">{{ $tx->product->sku ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if($tx->type === 'in')
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                            Masuk
                                        </span>
                                    @elseif($tx->type === 'out')
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                            Keluar
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">
                                            Opname
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center font-mono font-bold {{ $tx->type === 'in' ? 'text-emerald-600' : ($tx->type === 'out' ? 'text-amber-600' : 'text-purple-600') }}">
                                    {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : '') }}{{ $tx->quantity }} Unit
                                </td>
                                <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $tx->user->name ?? 'User' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded {{ $tx->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($tx->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $tx->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-xs text-gray-400">Belum ada data transaksi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right (1 Col): AKTIVITAS PENGGUNA TERBARU (Audit Feed) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                        <h2 class="font-bold text-gray-900 dark:text-white">Aktivitas Pengguna</h2>
                    </div>
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Log Audit</span>
                </div>

                <!-- Feed List -->
                <div class="space-y-3.5 max-h-[360px] overflow-y-auto pr-1">
                    @forelse($userActivities as $act)
                    <div class="flex items-start space-x-3 p-3 rounded-xl bg-gray-50/80 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700 transition hover:bg-gray-100/70">
                        <!-- User Avatar Initials -->
                        <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold {{ $act->user_role === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300' : ($act->user_role === 'manager' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300') }}">
                            {{ $act->user_initials }}
                        </div>

                        <!-- Activity Text & Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                    {{ $act->user_name }}
                                </span>
                                <span class="text-[10px] text-gray-400 flex-shrink-0">
                                    {{ $act->time_ago }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                {{ $act->action_text }}: <span class="font-semibold text-gray-900 dark:text-white">{{ $act->product_name }}</span>
                            </p>

                            <div class="flex items-center space-x-2 mt-1.5">
                                <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded {{ $act->type === 'in' ? 'bg-emerald-100 text-emerald-800' : ($act->type === 'out' ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800') }}">
                                    {{ $act->type === 'in' ? '+' : ($act->type === 'out' ? '-' : '') }}{{ $act->quantity }} Unit
                                </span>
                                <span class="text-[10px] text-gray-400 capitalize">• {{ $act->status }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-xs text-gray-400 py-8">Belum ada log aktivitas pengguna.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
