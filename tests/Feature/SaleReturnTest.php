<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;
    private $firstVariant;
    private $secondVariant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner=User::create(['name'=>'Return Owner','username'=>'return-owner','email'=>'return-owner@example.test','password'=>'password123','is_active'=>true]);
        $this->owner->roles()->attach(Role::where('slug','owner')->value('id'));
        $this->branch=Branch::create(['name'=>'Return Branch','code'=>'RET1','is_active'=>true]);
        $category=Category::create(['name'=>'Return Shoes','slug'=>'return-shoes','is_active'=>true]);
        $p1=Product::create(['category_id'=>$category->id,'name'=>'Original Shoe','is_active'=>true]);
        $p2=Product::create(['category_id'=>$category->id,'name'=>'Replacement Shoe','is_active'=>true]);
        $this->firstVariant=$p1->variants()->create(['size'=>'40','color'=>'Black','sku'=>'RET-ORIG-40','cost_price'=>1000,'selling_price'=>2500,'is_active'=>true]);
        $this->secondVariant=$p2->variants()->create(['size'=>'41','color'=>'Brown','sku'=>'RET-REPL-41','cost_price'=>1300,'selling_price'=>3000,'is_active'=>true]);
        $stock=app(StockService::class);
        $stock->change($this->branch->id,$this->firstVariant->id,5,$this->owner->id,'opening');
        $stock->change($this->branch->id,$this->secondVariant->id,5,$this->owner->id,'opening');
    }

    private function makeSale(int $qty=2): Sale
    {
        $this->actingAs($this->owner)->post(route('sales.store'),[
            'branch_id'=>$this->branch->id,'payment_method'=>'cash','amount_paid'=>10000,
            'items'=>[['product_variant_id'=>$this->firstVariant->id,'quantity'=>$qty]],
        ])->assertRedirect();
        return Sale::latest('id')->firstOrFail();
    }

    public function test_refund_return_restocking_is_recorded_and_duplicate_quantity_is_blocked(): void
    {
        $sale=$this->makeSale(2);
        $saleItem=$sale->items()->firstOrFail();
        $this->actingAs($this->owner)->post(route('returns.store',$sale),[
            'return_type'=>'refund','reason'=>'Wrong size','refund_method'=>'cash',
            'items'=>[['sale_item_id'=>$saleItem->id,'quantity'=>1]],
        ])->assertRedirect(route('returns.index'));

        $this->assertDatabaseHas('branch_stocks',[
            'branch_id'=>$this->branch->id,'product_variant_id'=>$this->firstVariant->id,'quantity'=>4,
        ]);
        $this->assertDatabaseHas('sale_returns',['sale_id'=>$sale->id,'returned_value'=>'2500.00','refund_amount'=>'2500.00']);
        $this->actingAs($this->owner)->post(route('returns.store',$sale),[
            'return_type'=>'refund','reason'=>'Duplicate request',
            'items'=>[['sale_item_id'=>$saleItem->id,'quantity'=>2]],
        ])->assertStatus(422);
    }

    public function test_exchange_restocks_returned_pair_and_deducts_replacement_pair(): void
    {
        $sale=$this->makeSale(2);
        $saleItem=$sale->items()->firstOrFail();
        $this->actingAs($this->owner)->post(route('returns.store',$sale),[
            'return_type'=>'exchange','reason'=>'Different style',
            'items'=>[['sale_item_id'=>$saleItem->id,'quantity'=>1]],
            'replacement_items'=>[['product_variant_id'=>$this->secondVariant->id,'quantity'=>1]],
            'payment_method'=>'cash','amount_paid'=>500,
        ])->assertRedirect(route('returns.index'));

        $this->assertDatabaseHas('branch_stocks',[
            'branch_id'=>$this->branch->id,'product_variant_id'=>$this->firstVariant->id,'quantity'=>4,
        ]);
        $this->assertDatabaseHas('branch_stocks',[
            'branch_id'=>$this->branch->id,'product_variant_id'=>$this->secondVariant->id,'quantity'=>4,
        ]);
        $this->assertDatabaseHas('payments',['method'=>'exchange_credit','amount'=>'2500.00']);
        $this->assertDatabaseHas('payments',['method'=>'cash','amount'=>'500.00']);
        $this->assertDatabaseHas('sale_returns',['sale_id'=>$sale->id,'return_type'=>'exchange','refund_amount'=>'0.00']);
    }
}
