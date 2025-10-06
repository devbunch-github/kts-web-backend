<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $accountantRole = Role::create(['name' => 'accountant', 'guard_name' => 'web']);
        $businessRole = Role::create(['name' => 'business', 'guard_name' => 'web']);

        // Create permissions (optional - based on your needs)
        $permissions = [
            'user_management',
            'role_management',
            'financial_reports',
            'business_management',
            'view_reports'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to super_admin role
        $superAdminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to other roles
        $accountantRole->givePermissionTo(['financial_reports', 'view_reports']);
        $businessRole->givePermissionTo(['business_management', 'view_reports']);

        // Create super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@email.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'), // Change this!
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign role to user
        $superAdmin->assignRole('super_admin');

        // Create sample accountant user
        $accountant = User::create([
            'name' => 'Accountant User',
            'email' => 'accountant@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $accountant->assignRole('accountant');

        // Create sample business user
        $businessUser = User::create([
            'name' => 'Business User',
            'email' => 'business@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $businessUser->assignRole('business');

        $this->command->info('Super Admin user created successfully!');
        $this->command->info('Email: superadmin@email.com');
        $this->command->info('Password: password123');
    }
}
