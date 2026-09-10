<?php

use App\Services\UserService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Manajemen User - Stockify')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    // Modal state
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $userId = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'staff';

    public string $successMessage = '';
    public string $errorMessage = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'email', 'password', 'userId', 'isEditing']);
        $this->role = 'staff';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEditModal(int $id, UserService $service): void
    {
        $this->resetErrorBag();
        $user = $service->findUser($id);

        if (!$user) {
            $this->errorMessage = 'Pengguna tidak ditemukan.';
            return;
        }

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = ''; // Keep blank unless changing
        $this->role = $user->role;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['name', 'email', 'password', 'userId', 'isEditing']);
        $this->resetErrorBag();
    }

    public function save(UserService $service): void
    {
        $emailRule = 'required|email|max:150|unique:users,email';
        if ($this->isEditing && $this->userId) {
            $emailRule .= ',' . $this->userId;
        }

        $passwordRule = $this->isEditing ? 'nullable|string|min:6' : 'required|string|min:6';

        $validated = $this->validate([
            'name' => 'required|string|max:100',
            'email' => $emailRule,
            'password' => $passwordRule,
            'role' => 'required|in:admin,manager,staff',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = $validated['password'];
        }

        if ($this->isEditing && $this->userId) {
            // Prevent removing last admin role if this is the only admin
            if ($this->userId === auth()->id() && $validated['role'] !== 'admin') {
                $this->addError('role', 'Anda tidak dapat menurunkan hak akses akun Admin Anda sendiri saat sedang aktif.');
                return;
            }

            $service->updateUser($this->userId, $userData);
            $this->successMessage = 'Data pengguna dan hak akses (role) berhasil diperbarui!';
        } else {
            $service->createUser($userData);
            $this->successMessage = 'Akun pengguna baru berhasil dibuat dan siap digunakan!';
        }

        $this->closeModal();
    }

    public function delete(int $id, UserService $service): void
    {
        // Safety 1: Prevent self-deletion
        if ($id === auth()->id()) {
            $this->errorMessage = 'Keamanan: Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan saat ini!';
            return;
        }

        $user = $service->findUser($id);
        if (!$user) {
            $this->errorMessage = 'Pengguna tidak ditemukan.';
            return;
        }

        // Safety 2: Prevent deleting users with transaction audit trail
        if ($user->stockTransactions()->count() > 0) {
            $this->errorMessage = "Pengguna '{$user->name}' tidak dapat dihapus karena memiliki riwayat transaksi mutasi stok. Anda dapat mengubah role atau mengganti passwordnya jika ingin membatasi akses.";
            return;
        }

        // Safety 3: Prevent deleting the only remaining admin
        if ($user->isAdmin() && $service->getUsersByRole('admin')->count() <= 1) {
            $this->errorMessage = 'Sistem harus memiliki setidaknya satu Administrator aktif!';
            return;
        }

        $service->deleteUser($id);
        $this->successMessage = "Akun pengguna '{$user->name}' berhasil dihapus dari sistem.";
    }

    public function with(UserService $service): array
    {
        $allUsers = $service->getAllUsers();

        // Metrics
        $totalUsers = $allUsers->count();
        $totalAdmin = $allUsers->where('role', 'admin')->count();
        $totalManager = $allUsers->where('role', 'manager')->count();
        $totalStaff = $allUsers->where('role', 'staff')->count();

        // Query with filters
        $users = $allUsers;

        if (!empty($this->search)) {
            $users = $users->filter(function($u) {
                return str_contains(strtolower($u->name), strtolower($this->search)) ||
                       str_contains(strtolower($u->email), strtolower($this->search));
            });
        }

        if (!empty($this->roleFilter)) {
            $users = $users->where('role', $this->roleFilter);
        }

        return [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'totalAdmin' => $totalAdmin,
            'totalManager' => $totalManager,
            'totalStaff' => $totalStaff,
        ];
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Pengguna & Role</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola akun pengguna Stockify, tentukan tingkat akses (Role), dan atur hak otorisasi.
                </p>
            </div>
            <div>
                <button wire:click="openCreateModal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-purple-600 rounded-xl hover:bg-purple-700 shadow-sm shadow-purple-500/20 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Tambah User Baru
                </button>
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

        <!-- Role Analytics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Pengguna -->
            <x-molecules.card title="Total Pengguna" total="{{ $totalUsers }}" description="Akun aktif terdaftar" color="gray">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-molecules.card>

            <!-- Total Admin -->
            <x-molecules.card title="Administrator" description="Hak akses penuh" total="{{ $totalAdmin }}" color="purple">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                 </svg>
            </x-molecules.card>

            <!-- Total Manajer Gudang -->
            <x-molecules.card title="Manajer Gudang" description="Operasional & Laporan" total="{{ $totalManager }}" color="blue">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-molecules.card>

            <!-- Total Staff Gudang -->
            <x-molecules.card title="Staff Gudang" description="Eksekusi fisik lapangan" total="{{ $totalStaff }}" color="emerald">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </x-molecules.card>
        </div>

        <!-- Filter Bar -->
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
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau email..."
                               class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-purple-500 focus:border-purple-500 block w-full pl-9 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- Role Filter -->
                    <div class="w-full sm:w-56">
                        <select wire:model.live="roleFilter" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Semua Tingkat Akses (Role)</option>
                            <option value="admin">Administrator</option>
                            <option value="manager">Manajer Gudang</option>
                            <option value="staff">Staff Gudang</option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ $users->count() }}</span> akun pengguna
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3.5">Pengguna</th>
                            <th class="px-6 py-3.5">Email</th>
                            <th class="px-6 py-3.5 text-center">Hak Akses (Role)</th>
                            <th class="px-6 py-3.5 text-center">Aktivitas Transaksi</th>
                            <th class="px-6 py-3.5">Terdaftar Sejak</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($users as $u)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm uppercase {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : ($u->role === 'manager' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300') }}">
                                        {{ substr($u->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-900 dark:text-white block">{{ $u->name }}</span>
                                        @if($u->id === auth()->id())
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                (Akun Anda)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-600 dark:text-gray-300">
                                {{ $u->email }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($u->role === 'admin')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">
                                        Administrator
                                    </span>
                                @elseif($u->role === 'manager')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                        Manajer Gudang
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        Staff Gudang
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $u->stockTransactions()->count() }} Mutasi
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <button wire:click="openEditModal({{ $u->id }})" title="Ubah User / Ganti Role" type="button" class="px-3 py-1.5 text-xs font-medium text-purple-600 hover:text-purple-800 bg-purple-50 hover:bg-purple-100 rounded-lg transition dark:bg-purple-900/30 dark:text-purple-300">
                                    Edit Role
                                </button>
                                @if($u->id !== auth()->id())
                                <button wire:click="delete({{ $u->id }})" wire:confirm="Apakah Anda yakin ingin menghapus user '{{ $u->name }}'?" title="Hapus User" type="button" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg transition dark:bg-red-900/30 dark:text-red-300">
                                    Hapus
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                Tidak ada akun pengguna yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- CREATE / EDIT USER MODAL -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $isEditing ? 'Ubah Pengguna & Hak Akses' : 'Tambah Pengguna Baru' }}
                </h3>
                <button wire:click="closeModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Nama Lengkap *</label>
                    <input wire:model="name" type="text" placeholder="Contoh: Budi Santoso"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Alamat Email *</label>
                    <input wire:model="email" type="email" placeholder="budi@stockify.test"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">
                        {{ $isEditing ? 'Kata Sandi Baru (Opsional)' : 'Kata Sandi *' }}
                    </label>
                    <input wire:model="password" type="password" placeholder="{{ $isEditing ? 'Biarkan kosong jika tidak diubah' : 'Minimal 6 karakter' }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Role Selector -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tentukan Hak Akses (Role) *</label>
                    <div class="grid grid-cols-1 gap-2.5">
                        <!-- Option 1: Admin -->
                        <label class="flex items-start p-3 border rounded-xl cursor-pointer transition {{ $role === 'admin' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-900/20 dark:border-purple-700' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <input wire:model.live="role" type="radio" value="admin" class="w-4 h-4 text-purple-600 mt-0.5 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900 dark:text-white">Administrator</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Akses tanpa batas ke seluruh data, konfigurasi pengguna, master data, dan laporan.</span>
                            </div>
                        </label>

                        <!-- Option 2: Manager -->
                        <label class="flex items-start p-3 border rounded-xl cursor-pointer transition {{ $role === 'manager' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <input wire:model.live="role" type="radio" value="manager" class="w-4 h-4 text-blue-600 mt-0.5 focus:ring-blue-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900 dark:text-white">Manajer Gudang</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Bertanggung jawab atas stok masuk/keluar, stock opname, monitoring stok menipis, dan laporan.</span>
                            </div>
                        </label>

                        <!-- Option 3: Staff -->
                        <label class="flex items-start p-3 border rounded-xl cursor-pointer transition {{ $role === 'staff' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20 dark:border-emerald-700' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <input wire:model.live="role" type="radio" value="staff" class="w-4 h-4 text-emerald-600 mt-0.5 focus:ring-emerald-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900 dark:text-white">Staff Gudang</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Fokus pada antrean tugas lapangan: memeriksa fisik barang masuk dan menyiapkan barang keluar.</span>
                            </div>
                        </label>
                    </div>
                    @error('role') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-purple-600 rounded-xl hover:bg-purple-700 shadow-sm shadow-purple-500/20">
                        {{ $isEditing ? 'Simpan Perubahan' : 'Buat Pengguna' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
