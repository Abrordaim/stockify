<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            
            // 1. Identitas Usaha & Branding
            $table->string('app_name')->default('Stockify');
            $table->string('company_name')->default('PT Stockify Logistik Indonesia');
            $table->text('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('logo_path')->nullable();

            // 2. Preferensi Regional & Inventaris
            $table->string('currency_symbol')->default('Rp');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('date_format')->default('d/m/Y');
            $table->integer('default_min_stock')->default(5);
            $table->string('sku_prefix')->default('PRD-');
            $table->string('in_prefix')->default('IN-');
            $table->string('out_prefix')->default('OUT-');

            // 3. Format Cetak & Dokumen Laporan
            $table->string('signee_name')->default('Budi Santoso, S.T.');
            $table->string('signee_title')->default('Kepala Gudang & Operasional');
            $table->text('footer_note')->default('Dokumen ini sah dan digenerate secara otomatis oleh sistem Stockify.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
