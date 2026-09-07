<?php

use App\Services\StockTransactionService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Tugas Operasional Gudang - Stockify')] class extends Component
{
    public string $activeTab = 'in'; // 'in' or 'out'

    public string $successMessage = '';
    public string $errorMessage = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function confirmTransaction(int $id, StockTransactionService $service): void
    {
        try {
            $service->updateTransactionStatus($id, 'completed');
            $this->successMessage = 'Tugas berhasil diselesaikan dan status transaksi telah diperbarui ke Selesai (Completed)!';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function cancelTransaction(int $id, StockTransactionService $service): void
    {
        try {
            $service->updateTransactionStatus($id, 'cancelled');
            $this->successMessage = 'Transaksi telah dibatalkan.';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function with(StockTransactionService $stockService): array
    {
        $pendingIn = $stockService->getPendingTransactions('in');
        $pendingOut = $stockService->getPendingTransactions('out');

        return [
            'pendingIn' => $pendingIn,
            'pendingOut' => $pendingOut,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-ping"></span>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar Tugas Lapangan (Task Queue)</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Antrean pemeriksaan fisik barang masuk dan penyiapan barang keluar untuk staff operasional gudang.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $pendingIn->count() + $pendingOut->count() }} Tugas Menunggu
                </span>
            </div>
        </div>

        <!-- Flash Alerts -->
        @if($successMessage)
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 rounded-xl border border-emerald-200 dark:bg-gray-800 dark:text-emerald-400 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ $successMessage }}</span>
            </div>
            <button wire:click="$set('successMessage', '')" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
        @endif

        @if($errorMessage)
        <div class="p-4 text-sm text-red-800 bg-red-50 rounded-xl border border-red-200 dark:bg-gray-800 dark:text-red-400 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', '')" class="text-red-600 hover:text-red-900">&times;</button>
        </div>
        @endif

        <!-- Task Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-2xl shadow-sm px-4 pt-2">
            <button wire:click="setTab('in')" type="button"
                    class="py-3 px-5 text-sm font-semibold border-b-2 transition flex items-center space-x-2 {{ $activeTab === 'in' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Barang Masuk Perlu Diperiksa</span>
                <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 font-bold">
                    {{ $pendingIn->count() }}
                </span>
            </button>

            <button wire:click="setTab('out')" type="button"
                    class="py-3 px-5 text-sm font-semibold border-b-2 transition flex items-center space-x-2 {{ $activeTab === 'out' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Barang Keluar Perlu Disiapkan</span>
                <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 font-bold">
                    {{ $pendingOut->count() }}
                </span>
            </button>
        </div>

        <!-- TAB CONTENT: PENDING INBOUND -->
        @if($activeTab === 'in')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white">Daftar Kiriman Barang Masuk yang Harus Diperiksa Fisik</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Pastikan jumlah fisik dan kondisi kemasan sesuai sebelum klik konfirmasi.</p>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($pendingIn as $task)
                <div class="p-5 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                {{ $task->product->sku ?? '-' }}
                            </span>
                            <h4 class="font-bold text-gray-900 dark:text-white text-base">
                                {{ $task->product->name ?? 'Produk' }}
                            </h4>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tanggal: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $task->date ? $task->date->format('d F Y') : '-' }}</span>
                            • Dicatat oleh: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $task->user->name ?? '-' }}</span>
                        </p>
                        @if($task->notes)
                        <div class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700 max-w-xl">
                            <span class="font-semibold text-gray-500">Catatan Pengiriman:</span> {{ $task->notes }}
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center space-x-4 self-end md:self-center">
                        <div class="text-right">
                            <span class="text-lg font-bold text-emerald-600 font-mono">+{{ $task->quantity }} Unit</span>
                            <span class="block text-[11px] text-gray-400">Menunggu Verifikasi</span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button wire:click="confirmTransaction({{ $task->id }})" wire:confirm="Konfirmasi fisik barang {{ $task->product->name ?? '' }} (+{{ $task->quantity }} unit) sudah diterima dengan benar?" type="button" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm shadow-emerald-500/20 transition">
                                ✓ Konfirmasi Diterima
                            </button>
                            <button wire:click="cancelTransaction({{ $task->id }})" wire:confirm="Tolak / batalkan penerimaan barang ini?" type="button" class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition dark:bg-red-900/30 dark:text-red-300">
                                Tolak
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Semua barang masuk telah selesai diperiksa! Tidak ada antrean tugas masuk.
                </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- TAB CONTENT: PENDING OUTBOUND -->
        @if($activeTab === 'out')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white">Daftar Barang Keluar yang Harus Disiapkan & Dipacking</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Ambil barang dari rak gudang dan kemas sesuai kuantitas sebelum konfirmasi keluar.</p>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($pendingOut as $task)
                <div class="p-5 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $task->product->sku ?? '-' }}
                            </span>
                            <h4 class="font-bold text-gray-900 dark:text-white text-base">
                                {{ $task->product->name ?? 'Produk' }}
                            </h4>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tanggal: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $task->date ? $task->date->format('d F Y') : '-' }}</span>
                            • Dicatat oleh: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $task->user->name ?? '-' }}</span>
                            • Stok Tersedia di Gudang: <span class="font-bold text-blue-600">{{ $task->product->current_stock ?? 0 }} unit</span>
                        </p>
                        @if($task->notes)
                        <div class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700 max-w-xl">
                            <span class="font-semibold text-gray-500">Instruksi / Keperluan:</span> {{ $task->notes }}
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center space-x-4 self-end md:self-center">
                        <div class="text-right">
                            <span class="text-lg font-bold text-amber-600 font-mono">-{{ $task->quantity }} Unit</span>
                            <span class="block text-[11px] text-gray-400">Perlu Penyiapan</span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button wire:click="confirmTransaction({{ $task->id }})" wire:confirm="Konfirmasi bahwa barang {{ $task->product->name ?? '' }} ({{ $task->quantity }} unit) sudah disiapkan dan keluar dari gudang?" type="button" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm shadow-amber-500/20 transition">
                                ✓ Konfirmasi Siap & Kirim
                            </button>
                            <button wire:click="cancelTransaction({{ $task->id }})" wire:confirm="Batalkan pesanan pengeluaran barang ini?" type="button" class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition dark:bg-red-900/30 dark:text-red-300">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Semua pengeluaran barang telah selesai disiapkan! Tidak ada antrean pengiriman.
                </div>
                @endforelse
            </div>
        </div>
        @endif

    </div>
</div>
