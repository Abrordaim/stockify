<?php

use App\Services\SettingService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Pengaturan Umum - Stockify')] class extends Component
{
    use WithFileUploads;

    public string $activeTab = 'profile';

    // 1. Identitas Usaha & Branding
    public string $app_name = '';
    public string $company_name = '';
    public ?string $company_address = '';
    public ?string $company_phone = '';
    public ?string $company_email = '';
    public $logo = null;
    public ?string $existingLogoUrl = null;

    // 2. Preferensi Regional & Inventaris
    public string $currency_symbol = 'Rp';
    public string $timezone = 'Asia/Jakarta';
    public string $date_format = 'd/m/Y';
    public int $default_min_stock = 5;
    public string $sku_prefix = 'PRD-';
    public string $in_prefix = 'IN-';
    public string $out_prefix = 'OUT-';

    // 3. Format Cetak & Dokumen Laporan
    public string $signee_name = '';
    public string $signee_title = '';
    public ?string $footer_note = '';

    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount(SettingService $service): void
    {
        $settings = $service->getSettings();

        $this->app_name = $settings->app_name ?? 'Stockify';
        $this->company_name = $settings->company_name ?? 'PT Stockify Logistik Indonesia';
        $this->company_address = $settings->company_address ?? '';
        $this->company_phone = $settings->company_phone ?? '';
        $this->company_email = $settings->company_email ?? '';
        $this->existingLogoUrl = $settings->logo_url;

        $this->currency_symbol = $settings->currency_symbol ?? 'Rp';
        $this->timezone = $settings->timezone ?? 'Asia/Jakarta';
        $this->date_format = $settings->date_format ?? 'd/m/Y';
        $this->default_min_stock = (int) ($settings->default_min_stock ?? 5);
        $this->sku_prefix = $settings->sku_prefix ?? 'PRD-';
        $this->in_prefix = $settings->in_prefix ?? 'IN-';
        $this->out_prefix = $settings->out_prefix ?? 'OUT-';

        $this->signee_name = $settings->signee_name ?? 'Budi Santoso, S.T.';
        $this->signee_title = $settings->signee_title ?? 'Kepala Gudang & Operasional';
        $this->footer_note = $settings->footer_note ?? 'Dokumen ini sah dan digenerate secara otomatis oleh sistem Stockify.';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function save(SettingService $service): void
    {
        $this->validate([
            // Tab 1: Identitas
            'app_name' => 'required|string|max:100',
            'company_name' => 'required|string|max:150',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:100',
            'logo' => 'nullable|image|max:2048', // max 2MB

            // Tab 2: Preferensi
            'currency_symbol' => 'required|string|max:10',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'default_min_stock' => 'required|integer|min:0',
            'sku_prefix' => 'required|string|max:20',
            'in_prefix' => 'required|string|max:20',
            'out_prefix' => 'required|string|max:20',

            // Tab 3: Cetak
            'signee_name' => 'required|string|max:100',
            'signee_title' => 'required|string|max:100',
            'footer_note' => 'nullable|string|max:500',
        ]);

        $data = [
            'app_name' => $this->app_name,
            'company_name' => $this->company_name,
            'company_address' => $this->company_address,
            'company_phone' => $this->company_phone,
            'company_email' => $this->company_email,
            'currency_symbol' => $this->currency_symbol,
            'timezone' => $this->timezone,
            'date_format' => $this->date_format,
            'default_min_stock' => $this->default_min_stock,
            'sku_prefix' => $this->sku_prefix,
            'in_prefix' => $this->in_prefix,
            'out_prefix' => $this->out_prefix,
            'signee_name' => $this->signee_name,
            'signee_title' => $this->signee_title,
            'footer_note' => $this->footer_note,
        ];

        $updated = $service->updateSettings($data, $this->logo);

        $this->existingLogoUrl = $updated->logo_url;
        $this->logo = null;
        $this->successMessage = 'Pengaturan umum aplikasi berhasil disimpan dan diperbarui!';


    }

    public function removeLogo(SettingService $service): void
    {
        $service->removeLogo();
        $this->existingLogoUrl = null;
        $this->logo = null;
        $this->successMessage = 'Logo aplikasi berhasil dihapus.';
    }
}; ?>

<div class="p-4 sm:ml-64 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="p-4 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan Umum Sistem</h1>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola identitas perusahaan, format mata uang, ambang batas inventaris, dan konfigurasi dokumen cetak resmi.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Akses Administrator
                </span>
            </div>
        </div>

        <!-- Flash Messages -->
        @if($successMessage)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300 flex items-center justify-between animate-in fade-in">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm font-medium">{{ $successMessage }}</span>
            </div>
            <button wire:click="$set('successMessage', '')" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif

        @if($errorMessage)
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-300 flex items-center justify-between animate-in fade-in">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', '')" class="text-rose-500 hover:text-rose-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif

        <!-- Settings Tabs Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 pt-4">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <!-- Tab 1: Profil Usaha -->
                    <button wire:click="setTab('profile')"
                            class="pb-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 transition duration-150 {{ $activeTab === 'profile' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Profil Usaha & Branding</span>
                    </button>

                    <!-- Tab 2: Preferensi & Inventaris -->
                    <button wire:click="setTab('preferences')"
                            class="pb-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 transition duration-150 {{ $activeTab === 'preferences' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Preferensi & Inventaris</span>
                    </button>

                    <!-- Tab 3: Format Cetak & Dokumen -->
                    <button wire:click="setTab('printing')"
                            class="pb-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 transition duration-150 {{ $activeTab === 'printing' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Format Cetak & Laporan</span>
                    </button>
                </nav>
            </div>

            <!-- Form Container -->
            <form wire:submit="save" class="p-6">

                <!-- TAB 1: PROFIL USAHA & BRANDING -->
                <div class="{{ $activeTab === 'profile' ? 'space-y-6' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Aplikasi -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Aplikasi / Sistem *</label>
                            <input wire:model="app_name" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Contoh: Stockify">
                            @error('app_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Nama yang tampil di bilah atas aplikasi, tab browser, dan email.</p>
                        </div>

                        <!-- Nama Perusahaan / Badan Usaha -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Perusahaan / Gudang *</label>
                            <input wire:model="company_name" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Contoh: PT Stockify Logistik Indonesia">
                            @error('company_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Nama resmi entitas bisnis untuk kop surat laporan cetak PDF.</p>
                        </div>

                        <!-- Email Resmi -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email Kontak</label>
                            <input wire:model="company_email" type="email"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="info@perusahaan.co.id">
                            @error('company_email') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- No. Telepon Resmi -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nomor Telepon / WhatsApp Gudang</label>
                            <input wire:model="company_phone" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="+62 812-3456-7890">
                            @error('company_phone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat Fisik Gudang / Kantor</label>
                        <textarea wire:model="company_address" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                  placeholder="Jl. Raya Industri Pergudangan No. 88, Blok A, Jakarta Barat 11840"></textarea>
                        @error('company_address') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-400 mt-1">Alamat ini akan dicetak di bagian header dokumen laporan resmi.</p>
                    </div>

                    <!-- Upload Logo -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Logo Perusahaan / Aplikasi</label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                            <!-- Logo Preview -->
                            <div class="w-32 h-32 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center p-2 bg-gray-50 dark:bg-gray-800/50 overflow-hidden relative group">
                                @if($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="max-h-full max-w-full object-contain">
                                @elseif($existingLogoUrl)
                                    <img src="{{ $existingLogoUrl }}" alt="Logo Saat Ini" class="max-h-full max-w-full object-contain">

                                @else
                                    <div class="text-center text-gray-400">
                                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-[10px] mt-1 block">Belum ada logo</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Upload Controls -->
                            <div class="space-y-3 flex-1">
                                <input wire:model="logo" type="file" accept="image/png, image/jpeg, image/webp"
                                       class="block w-full text-xs text-gray-900 border border-gray-300 rounded-xl cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-l-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 dark:file:bg-gray-600 dark:file:text-gray-200 hover:file:bg-blue-100">
                                @error('logo') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-400">Format yang didukung: PNG, JPG, WEBP. Maksimal ukuran file: 2 MB.</p>

                                @if($existingLogoUrl)
                                <button wire:click="removeLogo" type="button" class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Hapus Logo Saat Ini
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PREFERENSI & INVENTARIS -->
                <div class="{{ $activeTab === 'preferences' ? 'space-y-6' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Simbol Mata Uang -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Simbol Mata Uang *</label>
                            <input wire:model="currency_symbol" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Contoh: Rp atau IDR">
                            @error('currency_symbol') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Digunakan untuk format harga beli, harga jual, dan valuasi aset.</p>
                        </div>

                        <!-- Zona Waktu -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Zona Waktu (Timezone) *</label>
                            <select wire:model="timezone"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="Asia/Jakarta">WIB (Asia/Jakarta - UTC+7)</option>
                                <option value="Asia/Makassar">WITA (Asia/Makassar - UTC+8)</option>
                                <option value="Asia/Jayapura">WIT (Asia/Jayapura - UTC+9)</option>
                                <option value="UTC">UTC (Universal Time)</option>
                            </select>
                            @error('timezone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Zona waktu rujukan pencatatan timestamp mutasi stok.</p>
                        </div>

                        <!-- Format Tanggal -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Format Tanggal *</label>
                            <select wire:model="date_format"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="d/m/Y">DD/MM/YYYY (31/12/2026)</option>
                                <option value="Y-m-d">YYYY-MM-DD (2026-12-31)</option>
                                <option value="d M Y">DD Mon YYYY (31 Des 2026)</option>
                                <option value="d F Y">DD Month YYYY (31 Desember 2026)</option>
                            </select>
                            @error('date_format') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Aturan Inventaris & Stok -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Kebijakan Inventaris & Kode Dokumen</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Default Min Stock -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Default Stok Minimum *</label>
                                <input wire:model="default_min_stock" type="number" min="0"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('default_min_stock') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-400 mt-1">Batas peringatan low stock bawaan produk baru.</p>
                            </div>

                            <!-- Prefix SKU -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Prefix SKU Produk *</label>
                                <input wire:model="sku_prefix" type="text"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="PRD-">
                                @error('sku_prefix') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-400 mt-1">Awalan penomoran kode produk.</p>
                            </div>

                            <!-- Prefix Barang Masuk -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Prefix Barang Masuk *</label>
                                <input wire:model="in_prefix" type="text"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="IN-">
                                @error('in_prefix') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-400 mt-1">Awalan nomor dokumen masuk.</p>
                            </div>

                            <!-- Prefix Barang Keluar -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Prefix Barang Keluar *</label>
                                <input wire:model="out_prefix" type="text"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="OUT-">
                                @error('out_prefix') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-400 mt-1">Awalan nomor dokumen keluar.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: FORMAT CETAK & DOKUMEN LAPORAN -->
                <div class="{{ $activeTab === 'printing' ? 'space-y-6' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Penandatangan -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Penandatangan Laporan *</label>
                            <input wire:model="signee_name" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Contoh: Budi Santoso, S.T.">
                            @error('signee_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Nama pejabat yang berwenang menandatangani dokumen cetak PDF.</p>
                        </div>

                        <!-- Jabatan Penandatangan -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jabatan Penandatangan *</label>
                            <input wire:model="signee_title" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="Contoh: Kepala Bagian Gudang & Logistik">
                            @error('signee_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Keterangan jabatan di atas garis tanda tangan.</p>
                        </div>
                    </div>

                    <!-- Catatan Kaki / Footer Note Dokumen -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan Kaki (Footer Note) Dokumen</label>
                        <textarea wire:model="footer_note" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                  placeholder="Contoh: Dokumen ini sah dan digenerate secara otomatis oleh sistem Stockify."></textarea>
                        @error('footer_note') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-400 mt-1">Teks disclaimer atau informasi legal yang tampil di bagian bawah setiap lembar cetak laporan.</p>
                    </div>

                    <!-- Mockup Preview Surat -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-5 rounded-xl border border-gray-200 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-3">Pratinjau Footer Tanda Tangan:</span>
                        <div class="flex justify-between items-end max-w-lg bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400">
                            <div>
                                <span class="italic text-[11px] text-gray-400">{{ $footer_note ?: 'Catatan kaki dokumen...' }}</span>
                            </div>
                            <div class="text-center w-48">
                                <span class="block font-medium">{{ $signee_title ?: 'Jabatan Penandatangan' }}</span>
                                <div class="h-12"></div>
                                <span class="block font-bold text-gray-900 dark:text-white border-t border-gray-400 pt-1">
                                    {{ $signee_name ?: '(Nama Lengkap)' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 shadow-sm transition duration-150 disabled:opacity-50">
                        <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Simpan Perubahan Pengaturan</span>
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>
