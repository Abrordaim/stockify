<?php

use App\Services\ProductService;
use App\Services\StockTransactionService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Stock Opname (Penyesuaian Fisik) - Stockify')] class extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal state
    public bool $showModal = false;

    // Form fields
    public ?int $product_id = null;
    public int $system_stock = 0;
    public int $physical_stock = 0;
    public int $difference = 0;
    public string $date = '';
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
            'physical_stock' => 'required|integer|min:0',
            'date' => 'required|date',
            'notes' => 'required|string|max:500',
        ];
    }

    public function updatedProductId($val, ProductService $productService): void
    {
        if ($val) {
            $product = $productService->findProduct($val);
            $this->system_stock = $product ? $product->current_stock : 0;
            $this->physical_stock = $this->system_stock;
            $this->calculateDifference();
        } else {
            $this->system_stock = 0;
            $this->physical_stock = 0;
            $this->difference = 0;
        }
    }

    public function updatedPhysicalStock(): void
    {
        $this->calculateDifference();
    }

    private function calculateDifference(): void
    {
        $this->difference = (int) $this->physical_stock - (int) $this->system_stock;
    }

    public function openModal(): void
    {
        $this->reset(['product_id', 'system_stock', 'physical_stock', 'difference', 'notes']);
        $this->date = now()->toDateString();
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetErrorBag();
    }

    public function save(StockTransactionService $stockService): void
    {
        $validated = $this->validate();

        $this->calculateDifference();

        if ($this->difference === 0) {
            $this->addError('physical_stock', 'Jumlah fisik sama dengan stok sistem saat ini (tidak ada selisih yang disesuaikan).');
            return;
        }

        $stockService->recordAdjustment([
            'product_id' => $validated['product_id'],
            'user_id' => auth()->id(),
            'quantity' => $this->difference,
            'date' => $validated['date'],
            'status' => 'completed',
            'notes' => $validated['notes'],
        ]);

        $this->successMessage = "Penyesuaian stok opname berhasil disimpan! Selisih (" . ($this->difference > 0 ? "+{$this->difference}" : "{$this->difference}") . " unit) telah disesuaikan ke stok sistem.";

        $this->closeModal();
    }

    public function with(StockTransactionService $stockService, ProductService $productService): array
    {
        $products = $productService->getAllProducts();
        $transactions = $stockService->getTransactionsByType('adjustment');

        if (!empty($this->search)) {
            $transactions = $transactions->filter(function($tx) {
                $p = $tx->product;
                if (!$p) return false;
                return str_contains(strtolower($p->name), strtolower($this->search)) ||
                       str_contains(strtolower($p->sku), strtolower($this->search));
            });
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
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Stock Opname (Penyesuaian Fisik)</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Sinkronkan data stok tercatat di sistem dengan hasil hitungan fisik nyata di gudang.
                </p>
            </div>
            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
            <div>
                <button wire:click="openModal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Input Penyesuaian Opname
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
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk / SKU..."
                           class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total: <span class="font-bold text-gray-900 dark:text-white">{{ $transactions->count() }}</span> Riwayat Stock Opname
                </div>
            </div>
        </div>

        <!-- Opname Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal</th>
                            <th class="px-4 py-3.5">Produk</th>
                            <th class="px-4 py-3.5 text-center">Selisih Penyesuaian</th>
                            <th class="px-4 py-3.5">Petugas Opname</th>
                            <th class="px-4 py-3.5">Alasan / Keterangan</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
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
                            <td class="px-4 py-3.5 text-center whitespace-nowrap font-mono">
                                @if($tx->quantity > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        +{{ $tx->quantity }} Unit (Selisih Lebih)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                        {{ $tx->quantity }} Unit (Selisih Kurang)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $tx->user->name ?? '-' }}
                                <span class="block text-[10px] text-gray-400 uppercase">{{ $tx->user->role ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-600 dark:text-gray-300 max-w-sm leading-relaxed">
                                {{ $tx->notes ?: '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    Tersinkron
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                Belum ada riwayat stock opname / penyesuaian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Penyesuaian Opname -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Input Penyesuaian Stock Opname
                </h3>
                <button wire:click="closeModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Pilih Produk yang Diopname *</label>
                    <select wire:model.live="product_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">
                            [{{ $p->sku }}] {{ $p->name }} (Sistem: {{ $p->current_stock }} unit)
                        </option>
                        @endforeach
                    </select>
                    @error('product_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if($product_id)
                <div class="grid grid-cols-2 gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-xs border border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="text-gray-400 block">Stok Sistem Saat Ini:</span>
                        <span class="text-base font-bold text-gray-900 dark:text-white">{{ $system_stock }} unit</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Kalkulasi Selisih:</span>
                        @if($difference > 0)
                            <span class="text-base font-bold text-emerald-600">+{{ $difference }} unit (Lebih)</span>
                        @elseif($difference < 0)
                            <span class="text-base font-bold text-red-600">{{ $difference }} unit (Kurang)</span>
                        @else
                            <span class="text-base font-bold text-gray-500">0 unit (Cocok)</span>
                        @endif
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Jumlah Fisik Dihitung *</label>
                        <input wire:model.live="physical_stock" type="number" min="0"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('physical_stock') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Tanggal Opname *</label>
                        <input wire:model="date" type="date"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('date') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Alasan / Keterangan Penyesuaian *</label>
                    <textarea wire:model="notes" rows="3" placeholder="Contoh: Selisih hitung fisik berkala, 2 barang rusak karena basah di rak..."
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-500/20">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
