<?php

namespace App\Repositories\Contracts;

use App\Models\AppSetting;

interface SettingRepositoryInterface
{
    /**
     * Get the single application settings instance or create default.
     */
    public function getSettings(): AppSetting;

    /**
     * Update application settings.
     *
     * @param array<string, mixed> $data
     */
    public function updateSettings(array $data): AppSetting;
}
