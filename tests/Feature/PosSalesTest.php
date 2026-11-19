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
use Tests\\TestCase;

class PosSalesTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Branch $branch;
    private $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->branch = Branch::create(['name'=>'POS Branch','code'=>'POS1','is_active'=>true]);
        $this->cashier = User::create([
            'name'=>'POS Cashier','username'=>'pos-cashier','email'=>'pos-cashier@example.test',
            'password'=>'password123','branch_id'=>$this->branch->id,'is_active'=>true,
        ]);
        $this->cashier->roles()->attach(Role::where('slug','cashier')->value('id'));
        $category = Category::create(['name'=>'POS Shoes','slug'=>'pos-shoes','is_active'=>true]);
        $product = Product::create(['category_id'=>$category->id,'name'=>'POS Test Shoe','is_active'=>true]);
        $this->variant = $product->variants()->create([
            'size'=>'41','color'=>'Brown','sku'=>'POS-41-BRN','barcode'=>'987654321',
            'cost_price'=>1200,'selling_price'=>2500,'is_active'=>true,
        ]);
        app(StockService::class)->change($this->branch->id,$this->variant->id,5,$this->cashier->id,'opening');
    }

    public function test_cashier_can_complete_sale_and_stock_is_reduced_atomically(): void
    {
        $this->actingAs($this->cashier)->get(route('pos.index'))->assertOk()->assertSee('Point of Sale');
        $this->actingAs($this->cashier)->post(route('sales.store'),[
            'branch_id'=>$this->branch->id,
            'discount_amount'=>200,
            'tax_amount'=>0,
            'payment_method'=>'cash',
            'amount_paid'=>5000,
            'items'=>[['product_variant_id'=>$this->variant->id,'quantity'=>2]],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales',[
            'branch_id'=>$this->branch->id,'status'=>'completed',
            'subtotal'=>'5000.00','discount_amount'=>'200.00','total_amount'=>'4800.00',
            'amount_paid'=>'5000.00','change_amount'=>'200.00',
        ]);
        $this->assertDatabaseHas('branch_stocks',[
            'branch_id'=>$this->branch->id,'product_variant_id'=>$this->variant->id,'quantity'=>3,
        ]);
        $this->assertDatabaseHas('stock_movements',[
            'branch_id'=>$this->branch->id,'product_variant_id'=>$this->variant->id,
            'movement_type'=>'sale','quantity_change'=>-2,
        ]);
        $this->assertDatabaseHas('sale_items',['sku'=>'POS-41-BRN','quantity'=>2,'line_total'=>'5000.00']);
        $this->assertDatabaseHas('payments',['method'=>'cash','amount'=>'4800.00']);
    }

    public function test_sale_is_rejected_when_stock_is_insufficient_and_no_partial_sale_is_saved(): void
    {
        $this->actingAs($this->cashier)->post(route('sales.store'),[
            'branch_id'=>$this->branch->id,
            'payment_method'=>'cash',
            'amount_paid'=>25000,
            'items'=>[['product_variant_id'=>$this->variant->id,'quantity'=>50]],
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('sales',0);
        $this->assertDatabaseHas('branch_stocks',[
            'branch_id'=>$this->branch->id,'product_variant_id'=>$this->variant->id,'quantity'=>5,
        ]);
    }

    public function test_cashier_cannot_sell_from_another_branch(): void
    {
        $other = Branch::create(['name'=>'Other Branch','code'=>'POS2','is_active'=>true]);
        $this->actingAs($this->cashier)->post(route('sales.store'),[
            'branch_id'=>$other->id,'payment_method'=>'cash','amount_paid'=>2500,
            'items'=>[['product_variant_id'=>$this->variant->id,'quantity'=>1]],
        ])->assertForbidden();
        $this->assertDatabaseCount('sales',0);
    }
}
