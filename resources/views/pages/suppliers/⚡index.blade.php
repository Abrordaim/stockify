<?php

use App\Services\SupplierService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Data Supplier - Stockify')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $supplierId = null;

    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';

    public string $successMessage = '';
    public string $errorMessage = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|numeric|min:20',
            'email' => 'nullable|email|max:100',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'address', 'phone', 'email', 'supplierId', 'isEditing']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEditModal(int $id, SupplierService $service): void
    {
        $this->resetErrorBag();
        $supplier = $service->findSupplier($id);

        if (!$supplier) {
            $this->errorMessage = 'Data supplier tidak ditemukan.';
            return;
        }

        $this->supplierId = $supplier->id;
        $this->name = $supplier->name;
        $this->address = $supplier->address ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->email = $supplier->email ?? '';
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['name', 'address', 'phone', 'email', 'supplierId', 'isEditing']);
        $this->resetErrorBag();
    }

    public function save(SupplierService $service): void
    {
        $validated = $this->validate();

        if ($this->isEditing && $this->supplierId) {
            $service->updateSupplier($this->supplierId, $validated);
            $this->successMessage = 'Data supplier berhasil diperbarui!';
        } else {
            $service->createSupplier($validated);
            $this->successMessage = 'Supplier baru berhasil ditambahkan!';
        }

        $this->closeModal();
    }

    public function delete(int $id, SupplierService $service): void
    {
        $supplier = $service->findSupplier($id);

        if ($supplier && $supplier->products()->count() > 0) {
            $this->errorMessage = "Supplier '{$supplier->name}' tidak dapat dihapus karena masih memasok beberapa produk aktif.";
            return;
        }

        $service->deleteSupplier($id);
        $this->successMessage = 'Supplier berhasil dihapus.';
    }

    public function with(SupplierService $service): array
    {
        if (!empty($this->search)) {
            $suppliers = $service->searchSuppliers($this->search);
        } else {
            $suppliers = $service->getAllSuppliers();
        }

        return [
            'suppliers' => $suppliers,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Data Supplier</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola kontak dan informasi vendor pemasok stok barang Anda.
                </p>
            </div>
            @if(auth()->user()->isAdmin())
            <div>
                <button wire:click="openCreateModal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Supplier
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
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama supplier..."
                           class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-9 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total: <span class="font-bold text-gray-900 dark:text-white">{{ $suppliers->count() }}</span> Supplier
                </div>
            </div>
        </div>

        <!-- Supplier Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3.5">Nama Perusahaan</th>
                            <th class="px-6 py-3.5">Kontak</th>
                            <th class="px-6 py-3.5">Alamat</th>
                            <th class="px-6 py-3.5 text-center">Produk Disuplai</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($suppliers as $sup)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ $sup->name }}
                            </td>
                            <td class="px-6 py-4 text-xs space-y-1">
                                @if($sup->phone)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $sup->phone }}
                                </div>
                                @endif
                                @if($sup->email)
                                <div class="flex items-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $sup->email }}
                                </div>
                                @endif
                                @if(!$sup->phone && !$sup->email)
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                {{ $sup->address ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    {{ $sup->products()->count() }} Produk
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(auth()->user()->isAdmin())
                                <button wire:click="openEditModal({{ $sup->id }})" type="button" class="px-3 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition dark:bg-blue-900/30 dark:text-blue-300">
                                    Edit
                                </button>
                                <button wire:click="delete({{ $sup->id }})" wire:confirm="Apakah Anda yakin ingin menghapus supplier '{{ $sup->name }}'?" type="button" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg transition dark:bg-red-900/30 dark:text-red-300">
                                    Hapus
                                </button>
                                @else
                                <span class="text-xs text-gray-400 italic">Lihat Saja</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                Belum ada supplier yang cocok dengan pencarian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Create / Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $isEditing ? 'Edit Supplier' : 'Tambah Supplier Baru' }}
                </h3>
                <button wire:click="closeModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form wire:submit="save" class="p-5 space-y-4">
                <div>
                    <label for="sup_name" class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Nama Perusahaan / Supplier *</label>
                    <input wire:model="name" type="text" id="sup_name" placeholder="Contoh: PT Sumber Makmur"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="sup_phone" class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">No. Telepon</label>
                        <input wire:model="phone" type="text" id="sup_phone" placeholder="021-xxxx / 0812xxxx"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('phone') <span class="error text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="sup_email" class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                        <input wire:model="email" type="email" id="sup_email" placeholder="vendor@domain.com"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="sup_address" class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Alamat Lengkap</label>
                    <textarea wire:model="address" id="sup_address" rows="3" placeholder="Alamat gudang / kantor pemasok..."
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    @error('address') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-500/20">
                        {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Supplier' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
