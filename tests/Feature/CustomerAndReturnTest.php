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

class CustomerAndReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;
    private $original;
    private $replacement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner=User::create(['name'=>'Test Owner','username'=>'returns-owner','email'=>'returns-owner@example.test','password'=>'password123','is_active'=>true]);
        $this->owner->roles()->attach(Role::where('slug','owner')->value('id'));
        $this->branch=Branch::create(['name'=>'Returns Test','code'=>'RT01','is_active'=>true]);
        $category=Category::create(['name'=>'Returns Category','slug'=>'returns-category','is_active'=>true]);
        $p1=Product::create(['category_id'=>$category->id,'name'=>'Original Model','is_active'=>true]);
        $p2=Product::create(['category_id'=>$category->id,'name'=>'Exchange Model','is_active'=>true]);
        $this->original=$p1->variants()->create(['size'=>'40','color'=>'Black','sku'=>'ORIG-40','cost_price'=>1000,'selling_price'=>2500,'is_active'=>true]);
        $this->replacement=$p2->variants()->create(['size'=>'41','color'=>'Brown','sku'=>'EXCH-41','cost_price'=>1200,'selling_price'=>3000,'is_active'=>true]);
        $stock=app(StockService::class);
        $stock->change($this->branch->id,$this->original->id,5,$this->owner->id,'opening');
        $stock->change($this->branch->id,$this->replacement->id,5,$this->owner->id,'opening');
    }

    private function makeSale(): Sale
    {
        $this->actingAs($this->owner)->post(route('sales.store'),[
            'branch_id'=>$this->branch->id,'payment_method'=>'cash','amount_paid'=>5000,
            'items'=>[['product_variant_id'=>$this->original->id,'quantity'=>2]],
        ])->assertRedirect();
        return Sale::latest('id')->firstOrFail();
    }

    public function test_customer_can_be_created_and_searched(): void
    {
        $this->actingAs($this->owner)->post(route('customers.store'),[
            'name'=>'Test Customer','phone'=>'03001234567','email'=>'customer@example.test',
        ])->assertRedirect();
        $this->actingAs($this->owner)->get(route('customers.index',['search'=>'03001234567']))
            ->assertOk()->assertSee('Test Customer');
        $this->assertDatabaseHas('customers',['name'=>'Test Customer','phone'=>'03001234567']);
    }

    public function test_refund_restock_is_atomic_and_cannot_exceed_remaining_sold_quantity(): void
    {
        $sale=$this->makeSale();
        $item=$sale->items()->firstOrFail();
        $this->actingAs($this->owner)->post(route('returns.store',$sale),[
            'return_type'=>'refund','reason'=>'Incorrect size','refund_method'=>'cash',
            'items'=>[['sale_item_id'=>$item->id,'quantity'=>1]],
        ])->assertRedirect(route('returns.index'));
        $this->assertDatabaseHas('branch_stocks',['branch_id'=>$this->branch->id,'product_variant_id'=>$this->original->id,'quantity'=>4]);
        $this->assertDatabaseHas('sale_returns',['sale_id'=>$sale->id,'refund_amount'=>'2500.00']);

        $this->actingAs($this->owner)->post(route('returns.store',$sale),[
            'return_type'=>'refund','reason'=>'Repeat return',
            'items'=>[['sale_item_id'=>$item->id,'quantity'=>2]],
        ])->assertSessionHasErrors();
    }

    public function test_exchange_returns_old_item_and_deducts_replacement_with_balance_payment(): void
    {
        $sale=$this->makeSale();
        $item=$sale->items()->firstOrFail();
        $this->actingAs($this->owner)->post(route('returns.store',$sale),[
            'return_type'=>'exchange','reason'=>'Exchange for another model',
            'items'=>[['sale_item_id'=>$item->id,'quantity'=>1]],
            'replacement_items'=>[['product_variant_id'=>$this->replacement->id,'quantity'=>1]],
            'payment_method'=>'cash','amount_paid'=>500,
        ])->assertRedirect(route('returns.index'));

        $this->assertDatabaseHas('branch_stocks',['branch_id'=>$this->branch->id,'product_variant_id'=>$this->original->id,'quantity'=>4]);
        $this->assertDatabaseHas('branch_stocks',['branch_id'=>$this->branch->id,'product_variant_id'=>$this->replacement->id,'quantity'=>4]);
        $this->assertDatabaseHas('payments',['method'=>'exchange_credit','amount'=>'2500.00']);
        $this->assertDatabaseHas('payments',['method'=>'cash','amount'=>'500.00']);
        $this->assertDatabaseHas('sale_returns',['sale_id'=>$sale->id,'return_type'=>'exchange']);
    }
}
