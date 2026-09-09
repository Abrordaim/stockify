<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    protected StockTransactionService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockTransactionService::class);
    }

    public function test_admin_dashboard_renders_with_period_stats_chart_and_activities(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'PT Suplai']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'sku' => 'PRD-TEST',
            'name' => 'Keyboard Gaming',
            'purchase_price' => 100000,
            'selling_price' => 150000,
            'initial_stock' => 10,
            'minimum_stock' => 5,
        ]);

        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'in',
            'quantity' => 25,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Grafik Mutasi Stok Barang');
        $response->assertSee('Aktivitas Pengguna');
        $response->assertSee('7 Hari');
    }

    public function test_stock_transaction_service_period_stats_and_chart_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'PT Suplai']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'sku' => 'PRD-002',
            'name' => 'Monitor 24 Inch',
            'purchase_price' => 1200000,
            'selling_price' => 1600000,
            'initial_stock' => 5,
            'minimum_stock' => 2,
        ]);

        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'in',
            'quantity' => 10,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'out',
            'quantity' => 3,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $stats = $this->stockService->getTransactionStatsByPeriod('7_days');

        $this->assertEquals(1, $stats['in_count']);
        $this->assertEquals(10, $stats['in_qty']);
        $this->assertEquals(1, $stats['out_count']);
        $this->assertEquals(3, $stats['out_qty']);
        $this->assertEquals(7, $stats['net_qty']);

        $chart = $this->stockService->getChartDataByPeriod('7_days');

        $this->assertArrayHasKey('categories', $chart);
        $this->assertArrayHasKey('inSeries', $chart);
        $this->assertArrayHasKey('outSeries', $chart);
        $this->assertEquals(10, $chart['totalIn']);
        $this->assertEquals(3, $chart['totalOut']);
    }

    public function test_stock_transaction_service_user_activities(): void
    {
        $staff = User::factory()->create([
            'name' => 'Ahmad Staff',
            'role' => 'staff',
        ]);
        $category = Category::create(['name' => 'Logistik']);
        $supplier = Supplier::create(['name' => 'PT Logistik']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'sku' => 'LOG-001',
            'name' => 'Kardus Box Besar',
            'purchase_price' => 5000,
            'selling_price' => 7000,
            'initial_stock' => 50,
            'minimum_stock' => 10,
        ]);

        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $staff->id,
            'type' => 'in',
            'quantity' => 50,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $activities = $this->stockService->getRecentUserActivities(5);

        $this->assertCount(1, $activities);
        $act = $activities->first();
        $this->assertEquals('Ahmad Staff', $act->user_name);
        $this->assertEquals('staff', $act->user_role);
        $this->assertEquals('AH', $act->user_initials);
        $this->assertEquals('Menerima Barang Masuk', $act->action_text);
        $this->assertEquals('Kardus Box Besar', $act->product_name);
    }
}
