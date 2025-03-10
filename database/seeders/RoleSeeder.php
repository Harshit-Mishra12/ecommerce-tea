<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Define Roles
        $roles = [
            'admin',
            'editor',
            'viewer',
        ];

        // Define Permissions
        $permissions = [
            'manage users',
            'manage products',
            'manage orders',
            'manage coupons',
            'view reports',
        ];

        // Create Permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Roles and Assign Permissions
        foreach ($roles as $role) {
            $roleInstance = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

            // Assign all permissions to the 'admin' role
            if ($role === 'admin') {
                $roleInstance->givePermissionTo(Permission::all());
            } elseif ($role === 'editor') {
                $roleInstance->givePermissionTo(['manage products', 'manage orders']);
            } elseif ($role === 'viewer') {
                $roleInstance->givePermissionTo(['view reports']);
            }
        }
    }
}
