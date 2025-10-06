<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'create users', 'guard_name' => 'web'],
            ['name' => 'edit users', 'guard_name' => 'web'],
            ['name' => 'delete users', 'guard_name' => 'web'],
            ['name' => 'view users', 'guard_name' => 'web'],
            // Add more permissions as needed
        ];

        DB::table('permissions')->insert($permissions);

        // Assign all permissions to Super Admin role
        $superAdminRoleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $permissionIds = DB::table('permissions')->pluck('id');

        $rolePermissions = [];
        foreach ($permissionIds as $permissionId) {
            $rolePermissions[] = [
                'permission_id' => $permissionId,
                'role_id' => $superAdminRoleId,
            ];
        }

        DB::table('role_has_permissions')->insert($rolePermissions);

        $this->command->info('Role permissions seeded successfully!');
    }
}
