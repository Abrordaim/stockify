<?php

use App\Services\ProductService;
use App\Services\StockTransactionService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Barang Masuk (Inbound) - Stockify')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    // Modal state
    public bool $showModal = false;

    // Form fields
    public ?int $product_id = null;
    public int $quantity = 1;
    public string $date = '';
    public string $status = 'completed';
    public string $notes = '';

    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'date' => 'required|date',
            'status' => 'required|in:completed,pending',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function openModal(): void
    {
        $this->reset(['product_id', 'notes']);
        $this->quantity = 1;
        $this->date = now()->toDateString();
        $this->status = 'completed';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetErrorBag();
    }

    public function save(StockTransactionService $service): void
    {
        $validated = $this->validate();

        $service->recordStockIn([
            'product_id' => $validated['product_id'],
            'user_id' => auth()->id(),
            'quantity' => $validated['quantity'],
            'date' => $validated['date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->successMessage = $validated['status'] === 'completed'
            ? 'Penerimaan barang masuk berhasil dicatat! Stok produk bertambah.'
            : 'Penerimaan barang masuk disimpan sebagai pending untuk diverifikasi staff gudang.';

        $this->closeModal();
    }

    public function markCompleted(int $id, StockTransactionService $service): void
    {
        try {
            $service->updateTransactionStatus($id, 'completed');
            $this->successMessage = 'Transaksi barang masuk telah dikonfirmasi dan stok berhasil ditambahkan!';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function markCancelled(int $id, StockTransactionService $service): void
    {
        try {
            $service->updateTransactionStatus($id, 'cancelled');
            $this->successMessage = 'Transaksi barang masuk berhasil dibatalkan.';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function with(StockTransactionService $stockService, ProductService $productService): array
    {
        $products = $productService->getAllProducts();
        $transactions = $stockService->getTransactionsByType('in');

        // Filter search by product name/SKU
        if (!empty($this->search)) {
            $transactions = $transactions->filter(function($tx) {
                $p = $tx->product;
                if (!$p) return false;
                return str_contains(strtolower($p->name), strtolower($this->search)) ||
                       str_contains(strtolower($p->sku), strtolower($this->search));
            });
        }

        // Filter by status
        if (!empty($this->statusFilter)) {
            $transactions = $transactions->where('status', $this->statusFilter);
        }

        return [
            'transactions' => $transactions,
            'products' => $products,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Penerimaan Barang Masuk (Goods In)</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Catat dan pantau transaksi penambahan stok dari supplier ke gudang.
                </p>
            </div>
            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
            <div>
                <button wire:click="openModal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Catat Barang Masuk
                </button>
            </div>
            @endif
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

        <!-- Filter & Search Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk / SKU..."
                               class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full pl-9 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div class="w-full sm:w-48">
                        <select wire:model.live="statusFilter" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Semua Status</option>
                            <option value="completed">Completed (Selesai)</option>
                            <option value="pending">Pending (Menunggu)</option>
                            <option value="cancelled">Cancelled (Batal)</option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total: <span class="font-bold text-gray-900 dark:text-white">{{ $transactions->count() }}</span> Transaksi Masuk
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal</th>
                            <th class="px-4 py-3.5">Produk</th>
                            <th class="px-4 py-3.5">Kuantitas Masuk</th>
                            <th class="px-4 py-3.5">Dicatat Oleh</th>
                            <th class="px-4 py-3.5">Catatan / No. Surat</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($transactions as $tx)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 text-xs font-mono text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $tx->date ? $tx->date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">
                                {{ $tx->product->name ?? 'Produk Dihapus' }}
                                <span class="block text-[11px] font-mono text-gray-400">{{ $tx->product->sku ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-sm font-bold text-emerald-600 whitespace-nowrap">
                                +{{ $tx->quantity }} Unit
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $tx->user->name ?? '-' }}
                                <span class="block text-[10px] text-gray-400 uppercase">{{ $tx->user->role ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                {{ $tx->notes ?: '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if(in_array($tx->status, ['Diterima', 'completed']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Diterima
                                    </span>
                                @elseif(in_array($tx->status, ['Pending', 'pending']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 animate-pulse">
                                        Pending
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                        Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                                @if($tx->status === 'pending')
                                <button wire:click="markCompleted({{ $tx->id }})" wire:confirm="Konfirmasi bahwa fisik barang sudah diperiksa dan sesuai?" type="button" class="px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg dark:bg-emerald-900/30 dark:text-emerald-300">
                                    Konfirmasi
                                </button>
                                <button wire:click="markCancelled({{ $tx->id }})" wire:confirm="Batalkan transaksi ini?" type="button" class="px-2.5 py-1 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg dark:bg-red-900/30 dark:text-red-300">
                                    Batal
                                </button>
                                @else
                                <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                Belum ada riwayat transaksi barang masuk.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Catat Barang Masuk -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Catat Penerimaan Barang Masuk
                </h3>
                <button wire:click="closeModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Pilih Produk *</label>
                    <select wire:model="product_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">-- Pilih Barang yang Diterima --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">
                            [{{ $p->sku }}] {{ $p->name }} (Stok Saat Ini: {{ $p->current_stock }})
                        </option>
                        @endforeach
                    </select>
                    @error('product_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Jumlah Masuk (Unit) *</label>
                        <input wire:model="quantity" type="number" min="1"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('quantity') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Tanggal Penerimaan *</label>
                        <input wire:model="date" type="date"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('date') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Status Transaksi *</label>
                    <select wire:model="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="completed">Completed (Langsung Tambahkan ke Stok)</option>
                        <option value="pending">Pending (Menunggu Pemeriksaan Fisik Staff)</option>
                    </select>
                    @error('status') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Catatan / No. Surat Jalan</label>
                    <textarea wire:model="notes" rows="2" placeholder="Contoh: No. Surat Jalan SJ-2026-001 dari PT Sumber Makmur..."
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm shadow-emerald-500/20">
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
