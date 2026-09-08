<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StockOpnamePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-stock-opname',
            'view-stock-opname-report',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Roles to assign manage-stock-opname
        $rolesToAssign = [
            'superadmin',
            'admin',
            'bm',
            'bm_gsk',
            'manager_operasional',
            'manager_operasional_gsk',
        ];

        foreach ($rolesToAssign as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(['manage-stock-opname', 'view-stock-opname-report']);
            }
        }

        $this->command?->info('Permissions untuk Stock Opname berhasil dibuat dan di-assign ke roles BM / BM GSK.');
    }
}
