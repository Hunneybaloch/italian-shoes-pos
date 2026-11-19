<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner=User::create(['name'=>'System Owner','username'=>'system-owner','email'=>'system-owner@example.test','password'=>'password123','is_active'=>true]);
        $this->owner->roles()->attach(Role::where('slug','owner')->value('id'));
        $this->branch=Branch::create(['name'=>'Admin Branch','code'=>'ADM1','is_active'=>true]);
    }

    public function test_owner_can_create_branch_manager_and_employee_is_audited(): void
    {
        $this->actingAs($this->owner)->post(route('employees.store'),[
            'name'=>'Branch Manager','username'=>'admin-manager','email'=>'manager@example.test',
            'password'=>'secret123','password_confirmation'=>'secret123',
            'role'=>'branch-manager','branch_id'=>$this->branch->id,'is_active'=>1,
        ])->assertRedirect(route('employees.index'));

        $manager=User::where('username','admin-manager')->firstOrFail();
        $this->assertSame($this->branch->id,$manager->branch_id);
        $this->assertTrue($manager->hasRole('branch-manager'));
        $this->assertTrue(password_verify('secret123',$manager->password));
        $this->assertDatabaseHas('activity_logs',['action'=>'employee.created','module'=>'employees']);
    }

    public function test_branch_manager_can_create_only_branch_staff_and_cannot_assign_another_branch(): void
    {
        $manager=User::create(['name'=>'Manager','username'=>'branch-manager-test','email'=>'branch-manager-test@example.test','password'=>'password123','branch_id'=>$this->branch->id,'is_active'=>true]);
        $manager->roles()->attach(Role::where('slug','branch-manager')->value('id'));

        $this->actingAs($manager)->post(route('employees.store'),[
            'name'=>'Branch Cashier','username'=>'branch-cashier','email'=>'branch-cashier@example.test',
            'password'=>'secret123','password_confirmation'=>'secret123','role'=>'cashier','is_active'=>1,
        ])->assertRedirect(route('employees.index'));

        $cashier=User::where('username','branch-cashier')->firstOrFail();
        $this->assertSame($this->branch->id,$cashier->branch_id);
        $this->assertTrue($cashier->hasRole('cashier'));

        $this->actingAs($manager)->from(route('employees.create'))->post(route('employees.store'),[
            'name'=>'Forbidden Owner','username'=>'forbidden-owner','email'=>'forbidden@example.test',
            'password'=>'secret123','password_confirmation'=>'secret123','role'=>'owner',
        ])->assertSessionHasErrors('role');
    }

    public function test_owner_can_save_store_settings_and_view_audit_log(): void
    {
        $this->actingAs($this->owner)->put(route('settings.update'),[
            'store_name'=>'Italian Shoes Quetta','store_phone'=>'0811234567',
            'store_address'=>'Quetta, Balochistan','receipt_footer'=>'Visit us again',
            'default_tax_rate'=>'5','currency'=>'PKR','timezone'=>'Asia/Karachi',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings',['key'=>'store_name','value'=>'Italian Shoes Quetta']);
        $this->assertDatabaseHas('activity_logs',['action'=>'settings.updated','module'=>'settings']);
        $this->actingAs($this->owner)->get(route('activity-logs.index'))->assertOk()->assertSee('settings.updated');
    }

    public function test_branch_manager_cannot_view_another_branches_employee_record(): void
    {
        $manager=User::create(['name'=>'Manager','username'=>'scoped-manager','email'=>'scoped-manager@example.test','password'=>'password123','branch_id'=>$this->branch->id,'is_active'=>true]);
        $manager->roles()->attach(Role::where('slug','branch-manager')->value('id'));
        $otherBranch=Branch::create(['name'=>'Other Admin Branch','code'=>'ADM2','is_active'=>true]);
        $other=User::create(['name'=>'Other Cashier','username'=>'other-cashier','email'=>'other-cashier@example.test','password'=>'password123','branch_id'=>$otherBranch->id,'is_active'=>true]);
        $other->roles()->attach(Role::where('slug','cashier')->value('id'));

        $this->actingAs($manager)->get(route('employees.edit',$other))->assertForbidden();
    }
}
