<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppSettingTest extends TestCase
{
    use RefreshDatabase;

    protected SettingService $settingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingService = app(SettingService::class);
    }

    public function test_admin_can_access_settings_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/settings');

        $response->assertStatus(200);
    }

    public function test_manager_cannot_access_settings_page_and_receives_403(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $response = $this->actingAs($manager)->get('/admin/settings');

        $response->assertStatus(403);
    }

    public function test_staff_cannot_access_settings_page_and_receives_403(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($staff)->get('/admin/settings');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_settings_page(): void
    {
        $response = $this->get('/admin/settings');

        $response->assertRedirect('/');
    }

    public function test_setting_service_can_retrieve_default_settings(): void
    {
        $settings = $this->settingService->getSettings();

        $this->assertInstanceOf(AppSetting::class, $settings);
        $this->assertEquals('Stockify', $settings->app_name);
        $this->assertEquals('Rp', $settings->currency_symbol);
        $this->assertEquals('Asia/Jakarta', $settings->timezone);
    }

    public function test_setting_service_can_update_settings(): void
    {
        $updateData = [
            'app_name' => 'Stockify Enterprise',
            'company_name' => 'PT Maju Logistik Makmur',
            'company_address' => 'Kawasan Pergudangan Modern Blok C2, Cikarang',
            'company_phone' => '+62 811-2233-4455',
            'company_email' => 'contact@majulogistik.com',
            'currency_symbol' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'date_format' => 'Y-m-d',
            'default_min_stock' => 15,
            'sku_prefix' => 'SKU-',
            'in_prefix' => 'INB-',
            'out_prefix' => 'OUTB-',
            'signee_name' => 'Dr. Hendra Wijaya',
            'signee_title' => 'Direktur Operasional Logistik',
            'footer_note' => 'Dokumen resmi PT Maju Logistik Makmur.',
        ];

        $updated = $this->settingService->updateSettings($updateData);

        $this->assertEquals('Stockify Enterprise', $updated->app_name);
        $this->assertEquals('PT Maju Logistik Makmur', $updated->company_name);
        $this->assertEquals('IDR', $updated->currency_symbol);
        $this->assertEquals(15, $updated->default_min_stock);

        $this->assertDatabaseHas('app_settings', [
            'app_name' => 'Stockify Enterprise',
            'company_name' => 'PT Maju Logistik Makmur',
            'currency_symbol' => 'IDR',
            'default_min_stock' => 15,
            'signee_name' => 'Dr. Hendra Wijaya',
        ]);
    }

    public function test_layout_renders_dynamic_title_and_favicon(): void
    {
        $this->settingService->updateSettings([
            'app_name' => 'GudangKu Smart',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('GudangKu Smart');
        $response->assertSee('<link rel="icon"', false);
    }
}
