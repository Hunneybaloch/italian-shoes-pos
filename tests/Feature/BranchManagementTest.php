<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::create([
            'name' => 'Test Owner',
            'username' => 'test-owner',
            'email' => 'owner@example.test',
            'password' => 'owner-password',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->firstOrFail();
        $this->owner->roles()->attach($ownerRole->id);
    }

    public function test_owner_can_view_branch_management_pages(): void
    {
        $this->actingAs($this->owner)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertSee('Branch Management');

        $this->actingAs($this->owner)
            ->get(route('branches.create'))
            ->assertOk()
            ->assertSee('First Branch Manager Account');
    }

    public function test_non_owner_cannot_access_branch_management(): void
    {
        $employee = User::create([
            'name' => 'Regular Employee',
            'username' => 'regular-employee',
            'email' => 'employee@example.test',
            'password' => 'employee-password',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($employee)
            ->get(route('branches.index'))
            ->assertForbidden();
    }

    public function test_owner_can_create_a_branch_and_its_first_manager_atomically(): void
    {
        $this->actingAs($this->owner)
            ->post(route('branches.store'), [
                'name' => 'Quetta Main Branch',
                'code' => 'qta-main',
                'address' => 'Quetta',
                'phone' => '03001234567',
                'manager_name' => 'Branch Manager',
                'username' => 'qta-manager',
                'email' => 'manager@example.test',
                'password' => 'secure123',
                'password_confirmation' => 'secure123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'name' => 'Quetta Main Branch',
            'code' => 'QTA-MAIN',
            'is_active' => true,
        ]);

        $branch = Branch::where('code', 'QTA-MAIN')->firstOrFail();
        $manager = User::where('username', 'qta-manager')->firstOrFail();

        $this->assertSame($branch->id, $manager->branch_id);
        $this->assertTrue($manager->hasRole('branch-manager'));
        $this->assertTrue(Hash::check('secure123', $manager->password));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->owner->id,
            'branch_id' => $branch->id,
            'action' => 'branch.created',
            'module' => 'branches',
        ]);
    }

    public function test_owner_can_edit_branch_details_without_changing_its_employees(): void
    {
        $branch = Branch::create([
            'name' => 'Old Branch Name',
            'code' => 'OLD-BR',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('branches.edit', $branch))
            ->assertOk()
            ->assertSee('Edit Old Branch Name');

        $this->actingAs($this->owner)
            ->put(route('branches.update', $branch), [
                'name' => 'Updated Branch Name',
                'code' => 'NEW-BR',
                'address' => 'Updated address',
                'phone' => '03111234567',
            ])
            ->assertRedirect(route('branches.show', $branch));

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Branch Name',
            'code' => 'NEW-BR',
            'address' => 'Updated address',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->owner->id,
            'branch_id' => $branch->id,
            'action' => 'branch.updated',
        ]);
    }

    public function test_owner_can_deactivate_and_reactivate_a_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Status Test Branch',
            'code' => 'STATUS-1',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('branches.toggle-status', $branch))
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('branches.toggle-status', $branch))
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => true,
        ]);
    }
}
