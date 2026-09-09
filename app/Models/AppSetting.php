<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'app_name',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'logo_path',
        'currency_symbol',
        'timezone',
        'date_format',
        'default_min_stock',
        'sku_prefix',
        'in_prefix',
        'out_prefix',
        'signee_name',
        'signee_title',
        'footer_note',
    ];

    /**
     * Get the logo web URL attribute.
     * Uses asset() to respect the active host and port (e.g. localhost:8000).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/' . ltrim($this->logo_path, '/'));
        }

        return null;
    }

    /**
     * Get the logo as a base64 Data URI for offline rendering & PDF printing.
     */
    public function getLogoBase64Attribute(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            $fullPath = Storage::disk('public')->path($this->logo_path);
            if (file_exists($fullPath)) {
                $mime = mime_content_type($fullPath) ?: 'image/png';
                $data = base64_encode(file_get_contents($fullPath));
                return "data:{$mime};base64,{$data}";
            }
        }

        return null;
    }
}
