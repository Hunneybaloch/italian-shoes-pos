<?php

namespace Tests\\Feature;

use App\\Models\\Branch;
use App\\Models\\Category;
use App\\Models\\Product;
use App\\Models\\Role;
use App\\Models\\User;
use App\\Services\\StockService;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Validation\\ValidationException;
use Tests\\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;
    private $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::create([
            'name' => 'Inventory Owner', 'username' => 'inventory-owner',
            'email' => 'inventory-owner@example.test', 'password' => 'password123', 'is_active' => true,
        ]);
        $this->owner->roles()->attach(Role::where('slug', 'owner')->value('id'));
        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TEST', 'is_active' => true]);
        $category = Category::create(['name' => 'Test Shoes', 'slug' => 'test-shoes', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Test Shoe', 'is_active' => true]);
        $this->variant = $product->variants()->create([
            'size' => '42', 'color' => 'Black', 'sku' => 'TEST-42-BLK',
            'cost_price' => 1000, 'selling_price' => 2000, 'is_active' => true,
        ]);
    }

    public function test_stock_service_records_every_movement_and_prevents_negative_stock(): void
    {
        $service = app(StockService::class);
        $service->change($this->branch->id, $this->variant->id, 8, $this->owner->id, 'opening', null, null, 'Opening count');
        $service->change($this->branch->id, $this->variant->id, -3, $this->owner->id, 'adjustment', null, null, 'Damaged pair');

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $this->branch->id, 'product_variant_id' => $this->variant->id, 'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);

        try {
            $service->change($this->branch->id, $this->variant->id, -6, $this->owner->id, 'adjustment');
            $this->fail('Negative stock should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $this->branch->id, 'product_variant_id' => $this->variant->id, 'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_owner_can_receive_a_purchase_into_a_selected_branch(): void
    {
        $this->actingAs($this->owner)->post(route('purchases.store'), [
            'branch_id' => $this->branch->id,
            'purchase_date' => now()->toDateString(),
            'discount_amount' => 100,
            'items' => [[
                'product_variant_id' => $this->variant->id,
                'quantity' => 4,
                'unit_cost' => 1200,
            ]],
        ])->assertRedirect(route('purchases.index'));

        $this->assertDatabaseHas('purchases', [
            'branch_id' => $this->branch->id, 'status' => 'received', 'subtotal' => '4800.00', 'total_amount' => '4700.00',
        ]);
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $this->branch->id, 'product_variant_id' => $this->variant->id, 'quantity' => 4,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $this->branch->id, 'product_variant_id' => $this->variant->id,
            'movement_type' => 'purchase_received', 'quantity_change' => 4,
        ]);
    }

    public function test_owner_can_request_approve_and_receive_a_branch_transfer(): void
    {
        $destination = Branch::create(['name' => 'Destination Branch', 'code' => 'DEST', 'is_active' => true]);
        app(StockService::class)->change($this->branch->id, $this->variant->id, 10, $this->owner->id, 'opening');

        $this->actingAs($this->owner)->post(route('transfers.store'), [
            'from_branch_id' => $this->branch->id,
            'to_branch_id' => $destination->id,
            'items' => [['product_variant_id' => $this->variant->id, 'quantity' => 6]],
        ])->assertRedirect(route('transfers.index'));

        $transfer = \\App\\Models\\StockTransfer::firstOrFail();
        $this->actingAs($this->owner)->patch(route('transfers.approve', $transfer))->assertRedirect();
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $this->branch->id, 'product_variant_id' => $this->variant->id, 'quantity' => 4,
        ]);

        $this->actingAs($this->owner)->patch(route('transfers.receive', $transfer))->assertRedirect();
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $destination->id, 'product_variant_id' => $this->variant->id, 'quantity' => 6,
        ]);
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'received']);
    }
}
