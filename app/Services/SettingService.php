<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    protected SettingRepositoryInterface $repository;

    public function __construct(SettingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve the current application settings.
     */
    public function getSettings(): AppSetting
    {
        return $this->repository->getSettings();
    }

    /**
     * Get a specific setting value with fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->getSettings();
        return $settings->$key ?? $default;
    }

    /**
     * Update application settings with optional logo upload.
     *
     * @param array<string, mixed> $data
     */
    public function updateSettings(array $data, ?UploadedFile $logo = null): AppSetting
    {
        if ($logo) {
            $currentSetting = $this->repository->getSettings();
            if ($currentSetting->logo_path && Storage::disk('public')->exists($currentSetting->logo_path)) {
                Storage::disk('public')->delete($currentSetting->logo_path);
            }

            $path = $logo->store('branding', 'public');
            $data['logo_path'] = $path;
        }

        return $this->repository->updateSettings($data);
    }

    /**
     * Remove the current logo.
     */
    public function removeLogo(): AppSetting
    {
        $currentSetting = $this->repository->getSettings();

        if ($currentSetting->logo_path && Storage::disk('public')->exists($currentSetting->logo_path)) {
            Storage::disk('public')->delete($currentSetting->logo_path);
        }

        return $this->repository->updateSettings(['logo_path' => null]);
    }
}
