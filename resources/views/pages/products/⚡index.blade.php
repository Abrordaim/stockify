<?php

use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SupplierService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Master Produk & Atribut - Stockify')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedCategory = '';

    // Modals state
    public bool $showFormModal = false;
    public bool $showDetailModal = false;
    public bool $isEditing = false;
    public ?int $productId = null;

    // Form fields
    public ?int $category_id = null;
    public ?int $supplier_id = null;
    public string $name = '';
    public string $sku = '';
    public string $description = '';
    public float $purchase_price = 0;
    public float $selling_price = 0;
    public int $minimum_stock = 5;

    // Dynamic Attributes list: [ ['name' => 'Warna', 'value' => 'Hitam'], ... ]
    public array $dynamicAttributes = [];

    // Selected product for detail view
    public ?object $detailProduct = null;

    public string $successMessage = '';
    public string $errorMessage = '';

    protected function rules(): array
    {
        $skuRule = 'required|string|max:50|unique:products,sku';
        if ($this->isEditing && $this->productId) {
            $skuRule .= ',' . $this->productId;
        }

        return [
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:150',
            'sku' => $skuRule,
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'dynamicAttributes.*.name' => 'nullable|string|max:50',
            'dynamicAttributes.*.value' => 'nullable|string|max:100',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function addAttributeRow(): void
    {
        $this->dynamicAttributes[] = ['name' => '', 'value' => ''];
    }

    public function removeAttributeRow(int $index): void
    {
        unset($this->dynamicAttributes[$index]);
        $this->dynamicAttributes = array_values($this->dynamicAttributes);
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'category_id', 'supplier_id', 'name', 'sku', 'description',
            'purchase_price', 'selling_price', 'minimum_stock', 'dynamicAttributes',
            'productId', 'isEditing'
        ]);
        $this->dynamicAttributes = [
            ['name' => '', 'value' => '']
        ];
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id, ProductService $service): void
    {
        $this->resetErrorBag();
        $product = $service->findProduct($id);

        if (!$product) {
            $this->errorMessage = 'Produk tidak ditemukan.';
            return;
        }

        $this->productId = $product->id;
        $this->category_id = $product->category_id;
        $this->supplier_id = $product->supplier_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description ?? '';
        $this->purchase_price = (float) $product->purchase_price;
        $this->selling_price = (float) $product->selling_price;
        $this->minimum_stock = (int) $product->minimum_stock;

        // Load existing attributes
        $this->dynamicAttributes = $product->productAttributes->map(fn($attr) => [
            'name' => $attr->name,
            'value' => $attr->value,
        ])->toArray();

        if (empty($this->dynamicAttributes)) {
            $this->dynamicAttributes = [['name' => '', 'value' => '']];
        }

        $this->isEditing = true;
        $this->showFormModal = true;
    }

    public function openDetailModal(int $id, ProductService $service): void
    {
        $product = $service->findProduct($id);
        if ($product) {
            $this->detailProduct = $product->load(['category', 'supplier', 'productAttributes', 'stockTransactions']);
            $this->showDetailModal = true;
        }
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailProduct = null;
    }

    public function save(ProductService $service): void
    {
        $validated = $this->validate();

        // Clean empty attributes
        $cleanAttributes = array_filter($this->dynamicAttributes, function($attr) {
            return !empty(trim($attr['name'] ?? '')) && !empty(trim($attr['value'] ?? ''));
        });

        $productData = [
            'category_id' => $validated['category_id'],
            'supplier_id' => $validated['supplier_id'],
            'name' => $validated['name'],
            'sku' => strtoupper($validated['sku']),
            'description' => $validated['description'] ?? null,
            'purchase_price' => $validated['purchase_price'],
            'selling_price' => $validated['selling_price'],
            'minimum_stock' => $validated['minimum_stock'],
        ];

        if ($this->isEditing && $this->productId) {
            $service->updateProduct($this->productId, $productData, $cleanAttributes);
            $this->successMessage = 'Produk dan atribut berhasil diperbarui!';
        } else {
            $service->createProduct($productData, $cleanAttributes);
            $this->successMessage = 'Produk baru dan atribut berhasil ditambahkan!';
        }

        $this->closeFormModal();
    }

    public function delete(int $id, ProductService $service): void
    {
        $service->deleteProduct($id);
        $this->successMessage = 'Produk beserta atributnya berhasil dihapus.';
    }

    public function with(ProductService $productService, CategoryService $categoryService, SupplierService $supplierService): array
    {
        $categories = $categoryService->getAllCategories();
        $suppliers = $supplierService->getAllSuppliers();

        // Query products with relations
        if (!empty($this->search)) {
            $products = $productService->searchProducts($this->search);
        } elseif (!empty($this->selectedCategory)) {
            $products = $productService->getProductsByCategory((int) $this->selectedCategory);
        } else {
            $products = $productService->getAllProducts();
        }

        return [
            'products' => $products,
            'categories' => $categories,
            'suppliers' => $suppliers,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Master Produk & Atribut</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola data barang, harga, batas stok minimum, dan atribut dinamis.
                </p>
            </div>
            @if(auth()->user()->isAdmin())
            <div>
                <button wire:click="openCreateModal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Produk Baru
                </button>
            </div>
            @endif
        </div>

        <!-- Flash Alert Messages -->
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
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk / SKU..."
                               class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- Category Filter Dropdown -->
                    <div class="w-full sm:w-56">
                        <select wire:model.live="selectedCategory" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400 self-end sm:self-center">
                    Total: <span class="font-bold text-gray-900 dark:text-white">{{ $products->count() }}</span> Produk
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Produk</th>
                            <th class="px-4 py-3.5">SKU</th>
                            <th class="px-4 py-3.5">Kategori / Supplier</th>
                            <th class="px-4 py-3.5 text-right">Harga Beli</th>
                            <th class="px-4 py-3.5 text-right">Harga Jual</th>
                            <th class="px-4 py-3.5 text-center">Stok</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($products as $prod)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">
                                <button wire:click="openDetailModal({{ $prod->id }})" class="hover:text-blue-600 text-left font-bold transition">
                                    {{ $prod->name }}
                                </button>
                                @if($prod->productAttributes->count() > 0)
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($prod->productAttributes->take(2) as $attr)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $attr->name }}: {{ $attr->value }}
                                    </span>
                                    @endforeach
                                    @if($prod->productAttributes->count() > 2)
                                    <span class="text-[10px] text-gray-400">+{{ $prod->productAttributes->count() - 2 }} atribut</span>
                                    @endif
                                </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $prod->sku }}
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ $prod->category->name ?? '-' }}
                                </span>
                                <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[140px]">{{ $prod->supplier->name ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs font-mono">
                                Rp {{ number_format($prod->purchase_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs font-mono font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold {{ $prod->is_low_stock ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                {{ $prod->current_stock }}
                                <span class="block text-[10px] text-gray-400 font-normal">Min: {{ $prod->minimum_stock }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($prod->is_low_stock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        Aman
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <button wire:click="openDetailModal({{ $prod->id }})" title="Lihat Detail" type="button" class="p-1.5 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                @if(auth()->user()->isAdmin())
                                <button wire:click="openEditModal({{ $prod->id }})" title="Edit Produk" type="button" class="p-1.5 text-blue-600 hover:text-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $prod->id }})" wire:confirm="Apakah Anda yakin ingin menghapus produk '{{ $prod->name }}'?" title="Hapus Produk" type="button" class="p-1.5 text-red-600 hover:text-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                Belum ada data produk yang cocok dengan pencarian / filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- CREATE / EDIT MODAL -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $isEditing ? 'Edit Produk' : 'Tambah Produk Baru' }}
                </h3>
                <button wire:click="closeFormModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form wire:submit="save" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kategori -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Kategori *</label>
                        <select wire:model="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Supplier *</label>
                        <select wire:model="supplier_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Nama Produk -->
                    <div class="sm:col-span-2">
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Nama Produk *</label>
                        <input wire:model="name" type="text" placeholder="Contoh: Laptop ASUS Vivobook 14"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">SKU (Kode Unik) *</label>
                        <input wire:model="sku" type="text" placeholder="ELK-001"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm font-mono uppercase rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('sku') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Harga Beli -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Harga Beli (Rp) *</label>
                        <input wire:model="purchase_price" type="number" min="0" step="100"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('purchase_price') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Harga Jual -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Harga Jual (Rp) *</label>
                        <input wire:model="selling_price" type="number" min="0" step="100"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('selling_price') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Minimum Stok -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Minimum Stok *</label>
                        <input wire:model="minimum_stock" type="number" min="0"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('minimum_stock') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Deskripsi Produk</label>
                    <textarea wire:model="description" rows="2" placeholder="Deskripsi spesifikasi barang..."
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    @error('description') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- DYNAMIC ATTRIBUTES BUILDER -->
                <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Atribut Dinamis Produk</h4>
                            <p class="text-xs text-gray-500">Tambahkan spesifikasi khusus (contoh: RAM, Ukuran, Warna, Garansi).</p>
                        </div>
                        <button wire:click="addAttributeRow" type="button" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg dark:bg-blue-900/30 dark:text-blue-300">
                            + Tambah Baris
                        </button>
                    </div>

                    <div class="space-y-2">
                        @foreach($dynamicAttributes as $index => $attr)
                        <div class="flex items-center space-x-2">
                            <input wire:model="dynamicAttributes.{{ $index }}.name" type="text" placeholder="Nama (e.g. Ukuran, RAM)"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg p-2 w-1/2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <input wire:model="dynamicAttributes.{{ $index }}.value" type="text" placeholder="Nilai (e.g. XL, 16GB)"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg p-2 w-1/2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <button wire:click="removeAttributeRow({{ $index }})" type="button" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg dark:hover:bg-red-900/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="closeFormModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-500/20">
                        {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Produk' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- DETAIL MODAL -->
    @if($showDetailModal && $detailProduct)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Spesifikasi Produk</h3>
                <button wire:click="closeDetailModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-mono text-blue-600 dark:text-blue-400 font-semibold">{{ $detailProduct->sku }}</span>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $detailProduct->name }}</h2>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $detailProduct->is_low_stock ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $detailProduct->is_low_stock ? 'Stok Menipis' : 'Stok Aman' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-xs">
                    <div>
                        <span class="text-gray-400 block">Kategori</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $detailProduct->category->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Supplier</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $detailProduct->supplier->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Harga Beli</span>
                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($detailProduct->purchase_price, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Harga Jual</span>
                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($detailProduct->selling_price, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Stok Saat Ini</span>
                        <span class="text-base font-bold {{ $detailProduct->is_low_stock ? 'text-red-600' : 'text-emerald-600' }}">{{ $detailProduct->current_stock }} Unit</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Batas Minimum Stok</span>
                        <span class="text-base font-bold text-gray-700 dark:text-gray-200">{{ $detailProduct->minimum_stock }} Unit</span>
                    </div>
                </div>

                @if($detailProduct->description)
                <div>
                    <h4 class="text-xs font-bold uppercase text-gray-400 tracking-wider">Deskripsi</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">{{ $detailProduct->description }}</p>
                </div>
                @endif

                <div>
                    <h4 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Atribut Dinamis (Spesifikasi)</h4>
                    @if($detailProduct->productAttributes->count() > 0)
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($detailProduct->productAttributes as $attr)
                        <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700 text-xs">
                            <span class="text-gray-400 block">{{ $attr->name }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $attr->value }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-gray-400 italic">Tidak ada atribut tambahan untuk produk ini.</p>
                    @endif
                </div>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 text-right">
                <button wire:click="closeDetailModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
