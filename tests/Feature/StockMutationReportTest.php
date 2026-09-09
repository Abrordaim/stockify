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

class StockMutationReportTest extends TestCase
{
    use RefreshDatabase;

    protected StockTransactionService $stockService;
    protected User $admin;
    protected User $staff;
    protected Category $category;
    protected Supplier $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockTransactionService::class);

        $this->admin = User::factory()->create(['name' => 'Budi Admin', 'role' => 'admin']);
        $this->staff = User::factory()->create(['name' => 'Siti Staff', 'role' => 'staff']);

        $this->category = Category::create(['name' => 'Peralatan Kantor']);
        $this->supplier = Supplier::create(['name' => 'PT Suplai Utama']);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'sku' => 'OF-001',
            'name' => 'Kertas A4 80gr',
            'purchase_price' => 45000,
            'selling_price' => 55000,
            'initial_stock' => 100,
            'minimum_stock' => 20,
        ]);
    }

    public function test_two_step_workflow_creation_sets_pending_and_same_stock_before_after(): void
    {
        $this->actingAs($this->admin);

        // 1. Admin mencatat transaksi barang masuk
        $tx = $this->stockService->recordStockIn([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 50,
            'date' => now()->toDateString(),
            'status' => 'Pending',
            'notes' => 'Surat jalan No. SJ-001',
        ]);

        $this->assertEquals('Pending', $tx->status);
        $this->assertEquals($this->admin->id, $tx->created_by);
        $this->assertNull($tx->confirmed_by);
        $this->assertNull($tx->confirmed_at);
        // Aturan: kalau status Pending, stok sebelum dan sesudah HARUS sama (belum berubah)
        $this->assertEquals(0, $tx->stock_before);
        $this->assertEquals(0, $tx->stock_after);
        $this->assertEquals(0, $this->product->fresh()->current_stock);
    }

    public function test_two_step_workflow_staff_confirmation_updates_status_and_stock(): void
    {
        $this->actingAs($this->admin);

        $tx = $this->stockService->recordStockIn([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 120,
            'date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        // 2. Staff Gudang melakukan verifikasi fisik & konfirmasi
        $this->actingAs($this->staff);
        $confirmedTx = $this->stockService->confirmTransaction($tx->id, $this->staff->id);

        $this->assertEquals('Diterima', $confirmedTx->status);
        $this->assertEquals($this->admin->id, $confirmedTx->created_by);
        $this->assertEquals($this->staff->id, $confirmedTx->confirmed_by);
        $this->assertNotNull($confirmedTx->confirmed_at);
        $this->assertEquals(0, $confirmedTx->stock_before);
        $this->assertEquals(120, $confirmedTx->stock_after);
        $this->assertEquals(120, $this->product->fresh()->current_stock);
    }

    public function test_two_step_workflow_staff_rejection_keeps_stock_unchanged(): void
    {
        // Berikan stok awal 100 dengan transaksi Diterima
        $this->stockService->recordStockIn([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'confirmed_by' => $this->staff->id,
            'quantity' => 100,
            'date' => now()->toDateString(),
            'status' => 'Diterima',
        ]);

        $this->assertEquals(100, $this->product->fresh()->current_stock);

        // Admin mencatat pengeluaran 30 unit (Pending)
        $txOut = $this->stockService->recordStockOut([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 30,
            'date' => now()->toDateString(),
            'status' => 'Pending',
            'notes' => 'Pengeluaran ke Divisi HR',
        ]);

        $this->assertEquals('Pending', $txOut->status);
        $this->assertEquals(100, $txOut->stock_before);
        $this->assertEquals(100, $txOut->stock_after); // Belum berubah

        // Staff menolak transaksi
        $rejectedTx = $this->stockService->rejectTransaction($txOut->id, $this->staff->id, 'Barang rusak saat dicek');

        $this->assertEquals('Ditolak', $rejectedTx->status);
        $this->assertEquals($this->staff->id, $rejectedTx->confirmed_by);
        $this->assertNotNull($rejectedTx->confirmed_at);
        $this->assertEquals(100, $rejectedTx->stock_before);
        $this->assertEquals(100, $rejectedTx->stock_after); // Tetap 100 (tidak berkurang)
        $this->assertEquals(100, $this->product->fresh()->current_stock);
    }

    public function test_mutation_report_summary_only_counts_confirmed_transactions(): void
    {
        // 1. Inbound Diterima: 50 unit (HARUS dihitung)
        $this->stockService->recordStockIn([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 50,
            'date' => now()->toDateString(),
            'status' => 'Diterima',
        ]);

        // 2. Inbound Pending: 30 unit (TIDAK boleh dihitung di total barang masuk)
        $this->stockService->recordStockIn([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 30,
            'date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        // 3. Outbound Dikeluarkan: 20 unit (HARUS dihitung)
        $this->stockService->recordStockOut([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 20,
            'date' => now()->toDateString(),
            'status' => 'Dikeluarkan',
        ]);

        // 4. Outbound Ditolak: 15 unit (TIDAK boleh dihitung di total barang keluar)
        $outPending = $this->stockService->recordStockOut([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'quantity' => 15,
            'date' => now()->toDateString(),
            'status' => 'Pending',
        ]);
        $this->stockService->rejectTransaction($outPending->id, $this->staff->id);

        $response = $this->actingAs($this->admin)->get('/reports/mutations');

        $response->assertStatus(200);
        $response->assertSee('Laporan Mutasi & Pergerakan Stok', false);
        $response->assertSee('Kertas A4 80gr');
        $response->assertSee('OF-001');

        // Verifikasi angka ringkasan di view
        $response->assertSee('+50');
        $response->assertSee('-20');
        $response->assertSee('Diterima');
        $response->assertSee('Dikeluarkan');
        $response->assertSee('Pending');
        $response->assertSee('Ditolak');
    }

    public function test_mutation_report_export_csv_and_print_view(): void
    {
        $this->stockService->recordStockIn([
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
            'confirmed_by' => $this->staff->id,
            'quantity' => 40,
            'date' => now()->toDateString(),
            'status' => 'Diterima',
            'notes' => 'Catatan tes export',
        ]);

        // Test Print View
        $printResponse = $this->actingAs($this->admin)->get('/reports/mutations/print');
        $printResponse->assertStatus(200);
        $printResponse->assertSee('OF-001');
        $printResponse->assertSee('Kertas A4 80gr');
        $printResponse->assertSee('Budi Admin');
        $printResponse->assertSee('Siti Staff');
        $printResponse->assertSee('Diterima');

        // Test CSV Stream Export
        $csvResponse = $this->actingAs($this->admin)->get('/reports/mutations/csv');
        $csvResponse->assertStatus(200);
        $this->assertTrue(str_contains($csvResponse->headers->get('content-disposition'), '.csv'));
    }
}
