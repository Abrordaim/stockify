<?php

namespace App\Repositories;

use App\Models\AppSetting;
use App\Repositories\Contracts\SettingRepositoryInterface;

class SettingRepository implements SettingRepositoryInterface
{
    /**
     * Get the single application settings instance or create default.
     */
    public function getSettings(): AppSetting
    {
        return AppSetting::firstOrCreate(
            ['id' => 1],
            [
                'app_name' => 'Stockify',
                'company_name' => 'PT Stockify Logistik Indonesia',
                'company_address' => 'Jl. Industri Pergudangan No. 88, Kawasan Niaga Barat, Jakarta Barat 11840',
                'company_phone' => '+62 812-3456-7890',
                'company_email' => 'support@stockify.test',
                'currency_symbol' => 'Rp',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'default_min_stock' => 5,
                'sku_prefix' => 'PRD-',
                'in_prefix' => 'IN-',
                'out_prefix' => 'OUT-',
                'signee_name' => 'Budi Santoso, S.T.',
                'signee_title' => 'Kepala Gudang & Operasional',
                'footer_note' => 'Dokumen ini sah dan digenerate secara otomatis oleh sistem Stockify.',
            ]
        );
    }

    /**
     * Update application settings.
     *
     * @param array<string, mixed> $data
     */
    public function updateSettings(array $data): AppSetting
    {
        $setting = $this->getSettings();
        $setting->update($data);

        return $setting->fresh();
    }
}
