<?php

use App\Services\AuthService;
use Livewire\Component;

use App\Services\SettingService;

new class extends Component
{
    public  $logo ;
    public string $app_name;

    public function mount(SettingService $service) {
        $settings = $service->getSettings();

        $this->logo = $settings->logo_url;
        $this->app_name = $settings->app_name;
    }

    public function logout(): void
    {
        /** @var AuthService $authService */
        $authService = app(AuthService::class);

        $authService->logout();

        $this->redirect(route('login'), navigate: true);
    }
};
?>

<div>
    <!-- Mobile Hamburger Button -->
    <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="inline-flex items-center p-2 mt-2 ml-3 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
        <span class="sr-only">Buka menu</span>
        <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
        </svg>
    </button>

    <!-- Sidebar Navigation -->
    <aside id="default-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidenav">
        <div class="flex flex-col justify-between h-full bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <!-- Top Section -->
            <div class="overflow-y-auto px-4 py-5 space-y-4">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3 px-2 pb-4 border-b border-gray-200 dark:border-gray-700">
                    @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $app_name }}" class="h-9 w-auto max-w-[60px] object-contain rounded-lg">
                    @endif
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">{{$app_name}}</h1>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Inventory System</span>
                    </div>
                </div>

                <!-- User Info Profile Card -->
                @auth
                <div class="p-3 bg-gray-50 rounded-xl dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-semibold flex items-center justify-center text-sm uppercase">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate dark:text-white">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-gray-500 truncate dark:text-gray-400">
                                {{ auth()->user()->email }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between">
                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Hak Akses:</span>
                        @if(auth()->user()->isAdmin())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300">
                                Admin
                            </span>
                        @elseif(auth()->user()->isManager())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                Manajer Gudang
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                Staff Gudang
                            </span>
                        @endif
                    </div>
                </div>
                @endauth

                <!-- Nav Menu List -->
                <ul class="space-y-1.5 font-medium text-sm">
                    <!-- Dashboard (All Roles) -->
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    @auth
                    <!-- SECTION: ADMIN ONLY -->
                    @if(auth()->user()->isAdmin())
                    <li class="pt-2">
                        <span class="px-2.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Administrator
                        </span>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('admin.users*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.users*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="ml-3">Manajemen User</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('admin.settings*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.settings*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="ml-3">Pengaturan Sistem</span>
                        </a>
                    </li>
                    @endif

                    <!-- SECTION: MASTER DATA (Admin & Manager) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <li class="pt-2">
                        <span class="px-2.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Master Data
                        </span>
                    </li>
                    <li>
                        <a href="{{ route('products.index') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('products.*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="ml-3">Produk & Atribut</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('categories.index') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('categories.*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('categories.*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span class="ml-3">Kategori Produk</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('suppliers.index') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('suppliers.*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('suppliers.*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="ml-3">Supplier</span>
                        </a>
                    </li>
                    @endif

                    <!-- SECTION: STOK & TRANSAKSI -->
                    <li class="pt-2">
                        <span class="px-2.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Operasional Stok
                        </span>
                    </li>
                    @if(auth()->user()->isStaff())
                    <li>
                        <a href="{{ route('stock.tasks') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('stock.tasks') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span class="ml-3">Tugas Lapangan (Task)</span>
                        </a>
                    </li>
                    @endif

                    <li>
                        <a href="{{ route('stock.in') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('stock.in') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            <span class="ml-3">Barang Masuk (In)</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('stock.out') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('stock.out') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="ml-3">Barang Keluar (Out)</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('stock.opname') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('stock.opname') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="ml-3">Stock Opname</span>
                        </a>
                    </li>

                    <!-- SECTION: LAPORAN (Admin & Manager) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <li class="pt-2">
                        <span class="px-2.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Laporan & Rekap
                        </span>
                    </li>
                    <li>
                        <a href="{{ route('reports.stock') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('reports.stock*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('reports.stock*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="ml-3">Laporan Stok</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.mutations') }}" class="flex items-center p-2.5 text-gray-700 rounded-lg dark:text-gray-200 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-700 transition duration-150 {{ request()->routeIs('reports.mutations*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-gray-700' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('reports.mutations*') ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="ml-3">Riwayat Mutasi</span>
                        </a>
                    </li>
                    @endif
                    @endauth
                </ul>
            </div>

            <!-- Bottom Section: Logout Button -->
            @auth
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="logout" type="button" class="w-full flex items-center justify-center space-x-2 py-2.5 px-4 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar (Logout)</span>
                </button>
            </div>
            @endauth
        </div>
    </aside>
</div>
