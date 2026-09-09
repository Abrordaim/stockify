<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    public function test_user_service_can_create_user_with_hashed_password(): void
    {
        $userData = [
            'name' => 'Budi Staff',
            'email' => 'budi@stockify.test',
            'password' => 'secret123',
            'role' => 'staff',
        ];

        $user = $this->userService->createUser($userData);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@stockify.test',
            'role' => 'staff',
        ]);
        $this->assertTrue(\Hash::check('secret123', $user->password));
    }

    public function test_user_service_can_update_user_role(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->userService->updateUser($user->id, [
            'role' => 'manager',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'manager',
        ]);
    }

    public function test_cannot_delete_user_with_transaction_history(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'PT Supplier']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'sku' => 'PRD-001',
            'name' => 'Mouse Wireless',
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'initial_stock' => 10,
            'minimum_stock' => 5,
        ]);

        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 10,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $this->assertGreaterThan(0, $user->stockTransactions()->count());
    }

    public function test_can_delete_user_without_transaction_history(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->userService->deleteUser($user->id);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
