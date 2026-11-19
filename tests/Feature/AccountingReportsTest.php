<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner=User::create(['name'=>'Accounts Owner','username'=>'accounts-owner','email'=>'accounts-owner@example.test','password'=>'password123','is_active'=>true]);
        $this->owner->roles()->attach(Role::where('slug','owner')->value('id'));
        $this->branch=Branch::create(['name'=>'Accounts Branch','code'=>'ACC1','is_active'=>true]);
    }

    public function test_cash_expense_creates_cash_outflow_and_is_reported(): void
    {
        $this->actingAs($this->owner)->post(route('expenses.store'),[
            'branch_id'=>$this->branch->id,'category'=>'Utilities','description'=>'Electricity bill',
            'amount'=>1500,'payment_method'=>'cash','expense_date'=>now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses',['branch_id'=>$this->branch->id,'amount'=>'1500.00','category'=>'Utilities']);
        $this->assertDatabaseHas('cash_transactions',['branch_id'=>$this->branch->id,'direction'=>'out','type'=>'expense','amount'=>'1500.00']);
        $this->actingAs($this->owner)->get(route('reports.index'))->assertOk()->assertSee('1,500.00');
    }

    public function test_supplier_payment_cannot_exceed_purchase_due_and_cash_is_logged(): void
    {
        $supplier=Supplier::create(['name'=>'Test Supplier','is_active'=>true]);
        $purchase=Purchase::create([
            'branch_id'=>$this->branch->id,'supplier_id'=>$supplier->id,'created_by'=>$this->owner->id,
            'invoice_number'=>'INV-ACC-01','status'=>'received','purchase_date'=>now()->toDateString(),
            'received_at'=>now(),'subtotal'=>5000,'discount_amount'=>0,'total_amount'=>5000,
        ]);

        $this->actingAs($this->owner)->post(route('purchases.payments.store',$purchase),[
            'amount'=>2000,'payment_method'=>'cash','reference'=>'CASH-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('supplier_payments',['purchase_id'=>$purchase->id,'amount'=>'2000.00']);
        $this->assertDatabaseHas('cash_transactions',['direction'=>'out','type'=>'supplier_payment','amount'=>'2000.00']);

        $this->actingAs($this->owner)->from(route('supplier-payments.index'))
            ->post(route('purchases.payments.store',$purchase),[
                'amount'=>4000,'payment_method'=>'cash',
            ])->assertStatus(422);
    }



    public function test_owner_can_export_filtered_sales_csv(): void
    {
        $response=$this->actingAs($this->owner)->get(route('reports.export',[
            'from'=>now()->toDateString(),
            'to'=>now()->toDateString(),
            'branch_id'=>$this->branch->id,
        ]));
        $response->assertOk();
        $response->assertHeader('content-type','text/csv; charset=UTF-8');
    }

    public function test_manual_cashbook_adjustment_is_visible_in_cashbook(): void
    {
        $this->actingAs($this->owner)->post(route('cashbook.store'),[
            'branch_id'=>$this->branch->id,
            'direction'=>'in',
            'amount'=>7500,
            'description'=>'Opening cash float',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_transactions',[
            'branch_id'=>$this->branch->id,
            'direction'=>'in',
            'type'=>'cash_adjustment',
            'amount'=>'7500.00',
        ]);
        $this->actingAs($this->owner)->get(route('cashbook.index'))
            ->assertOk()->assertSee('Opening cash float');
    }

    public function test_non_cash_expense_does_not_change_cash_ledger(): void
    {
        $this->actingAs($this->owner)->post(route('expenses.store'),[
            'branch_id'=>$this->branch->id,'category'=>'Rent','description'=>'Monthly rent',
            'amount'=>10000,'payment_method'=>'bank_transfer','expense_date'=>now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseCount('cash_transactions',0);
        $this->assertDatabaseHas('expenses',['payment_method'=>'bank_transfer','amount'=>'10000.00']);
    }
}
