<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all permissions
        $permissions = [
            // Transaksi & Komunikasi
            'view-pos',
            'view-stock',
            'manage-orders',
            'access-cs-chat',

            // Promo
            'manage-promos',

            // Katalog Pusat
            'view-catalog-menu',
            'manage-new-catalog',
            'manage-second-catalog',
            'manage-categories',
            'manage-brands',
            'manage-accurate-products',
            'manage-accurate-customers',
            'view-warehouse-stocks',

            // Pendukung Lainnya
            'manage-trade-in',
            'manage-buyback',
            'manage-qc',
            'manage-users',
            'manage-settings',
            'view_dashboard',

            // Client / Frontend
            'trade-in',
            'sell-phone'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Create Roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cs = Role::firstOrCreate(['name' => 'cs', 'guard_name' => 'web']);
        $fl = Role::firstOrCreate(['name' => 'fl', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles

        // Superadmin gets everything
        $superadmin->syncPermissions($permissions);

        // Admin gets most operational tasks but not users/settings
        $admin->syncPermissions([
            'view-pos',
            'view-stock',
            'manage-orders',
            'manage-promos',
            'view-catalog-menu',
            'manage-new-catalog',
            'manage-second-catalog',
            'manage-categories',
            'manage-brands',
            'manage-accurate-products',
            'manage-accurate-customers',
            'view-warehouse-stocks',
            'manage-trade-in',
            'manage-buyback',
            'manage-qc',
            'view_dashboard'
        ]);

        // CS gets chat, promo (optional), and stock view
        $cs->syncPermissions([
            'access-cs-chat',
            'view-stock',
            'manage-promos'
        ]);

        // FL (Frontliner) gets POS and Stock
        $fl->syncPermissions([
            'view-pos',
            'view-stock'
        ]);

        $this->command->info('Base permissions seeded and assigned to roles successfully.');
    }
}
