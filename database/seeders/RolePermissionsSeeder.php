<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // --- Roles ---
        $roles = [
            'super_admin',
            'admin',
            'accountant',
            'business',
            'business_admin',
            'user',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // --- Permissions ---
        $permissions = [
            'user_management',
            'role_management',
            'financial_reports',
            'business_management',
            'view_reports',
            'create_users',
            'edit_users',
            'delete_users',
            'view_users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // --- Assign Permissions ---
        $superAdmin = Role::where('name', 'super_admin')->first();
        $superAdmin->givePermissionTo(Permission::all());

        $accountant = Role::where('name', 'accountant')->first();
        $accountant->givePermissionTo(['financial_reports', 'view_reports']);

        $business = Role::where('name', 'business')->first();
        $business->givePermissionTo(['business_management', 'view_reports']);

        $businessAdmin = Role::where('name', 'business_admin')->first();
        $businessAdmin->givePermissionTo(['business_management', 'view_reports']);

        // --- Create default users (optional) ---
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@email.com'],
            [
                'name' => 'Super Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );
        $superAdminUser->assignRole('super_admin');

        $this->command->info('Roles, permissions, and sample users seeded successfully!');
    }
}
