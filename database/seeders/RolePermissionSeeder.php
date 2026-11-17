<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1. System roles
            $roles = [
                [
                    'name' => 'Owner',
                    'slug' => 'owner',
                    'description' => 'Complete access to all branches and system settings.',
                ],
                [
                    'name' => 'Branch Manager',
                    'slug' => 'branch-manager',
                    'description' => 'Manages operations of the assigned branch.',
                ],
                [
                    'name' => 'Cashier',
                    'slug' => 'cashier',
                    'description' => 'Handles sales and customer transactions.',
                ],
                [
                    'name' => 'Stock Manager',
                    'slug' => 'stock-manager',
                    'description' => 'Manages products and branch inventory.',
                ],
            ];

            foreach ($roles as $role) {
                DB::table('roles')->updateOrInsert(
                    ['slug' => $role['slug']],
                    array_merge($role, [
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ])
                );
            }

            // 2. System permissions
            $permissions = [
                // Dashboard
                ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],

                // Branches
                ['name' => 'View Branches', 'slug' => 'branches.view', 'module' => 'branches'],
                ['name' => 'Create Branches', 'slug' => 'branches.create', 'module' => 'branches'],
                ['name' => 'Edit Branches', 'slug' => 'branches.edit', 'module' => 'branches'],
                ['name' => 'Deactivate Branches', 'slug' => 'branches.deactivate', 'module' => 'branches'],

                // Employees
                ['name' => 'View Employees', 'slug' => 'employees.view', 'module' => 'employees'],
                ['name' => 'Create Employees', 'slug' => 'employees.create', 'module' => 'employees'],
                ['name' => 'Edit Employees', 'slug' => 'employees.edit', 'module' => 'employees'],
                ['name' => 'Deactivate Employees', 'slug' => 'employees.deactivate', 'module' => 'employees'],

                // Roles and permissions
                ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'settings'],
                ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'module' => 'settings'],

                // Products
                ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'products'],
                ['name' => 'Create Products', 'slug' => 'products.create', 'module' => 'products'],
                ['name' => 'Edit Products', 'slug' => 'products.edit', 'module' => 'products'],
                ['name' => 'Delete Products', 'slug' => 'products.delete', 'module' => 'products'],

                // Inventory
                ['name' => 'View Inventory', 'slug' => 'inventory.view', 'module' => 'inventory'],
                ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'module' => 'inventory'],
                ['name' => 'Transfer Stock', 'slug' => 'inventory.transfer', 'module' => 'inventory'],

                // Sales
                ['name' => 'View Sales', 'slug' => 'sales.view', 'module' => 'sales'],
                ['name' => 'Create Sales', 'slug' => 'sales.create', 'module' => 'sales'],
                ['name' => 'Cancel Sales', 'slug' => 'sales.cancel', 'module' => 'sales'],
                ['name' => 'Process Returns', 'slug' => 'sales.returns', 'module' => 'sales'],

                // Customers
                ['name' => 'View Customers', 'slug' => 'customers.view', 'module' => 'customers'],
                ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'module' => 'customers'],

                // Expenses
                ['name' => 'View Expenses', 'slug' => 'expenses.view', 'module' => 'expenses'],
                ['name' => 'Manage Expenses', 'slug' => 'expenses.manage', 'module' => 'expenses'],

                // Reports
                ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports'],
                ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'reports'],

                // Settings and audit
                ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'module' => 'settings'],
                ['name' => 'View Activity Logs', 'slug' => 'activity-logs.view', 'module' => 'settings'],
            ];

            foreach ($permissions as $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $permission['slug']],
                    array_merge($permission, [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ])
                );
            }

            // 3. Assign permissions to roles
            $allPermissions = DB::table('permissions')->pluck('id', 'slug');

            $rolePermissions = [
                'owner' => array_keys($allPermissions->toArray()),

                'branch-manager' => [
                    'dashboard.view',
                    'employees.view',
                    'employees.create',
                    'employees.edit',
                    'employees.deactivate',
                    'products.view',
                    'inventory.view',
                    'inventory.manage',
                    'inventory.transfer',
                    'sales.view',
                    'sales.create',
                    'sales.cancel',
                    'sales.returns',
                    'customers.view',
                    'customers.manage',
                    'expenses.view',
                    'expenses.manage',
                    'reports.view',
                    'reports.export',
                    'activity-logs.view',
                ],

                'cashier' => [
                    'dashboard.view',
                    'products.view',
                    'inventory.view',
                    'sales.view',
                    'sales.create',
                    'customers.view',
                    'customers.manage',
                ],

                'stock-manager' => [
                    'dashboard.view',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'inventory.view',
                    'inventory.manage',
                    'inventory.transfer',
                ],
            ];

            foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
                $roleId = DB::table('roles')
                    ->where('slug', $roleSlug)
                    ->value('id');

                foreach ($permissionSlugs as $permissionSlug) {
                    $permissionId = $allPermissions[$permissionSlug] ?? null;

                    if ($permissionId) {
                        DB::table('permission_role')->updateOrInsert(
                            [
                                'role_id' => $roleId,
                                'permission_id' => $permissionId,
                            ],
                            [
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }
                }
            }
        });
    }
}
