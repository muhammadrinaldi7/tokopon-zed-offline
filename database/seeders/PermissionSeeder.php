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

            // Inbound & QC
            'manage-inbound',
            'manage-qc-inspections',
            'manage-qc-templates',

            // Laporan
            'view-reporting',
            'reporting-dashboard',
            'reporting-sales',
            'reporting-products',
            'reporting-staff',

            // Pendukung Lainnya
            'manage-trade-in',
            'manage-buyback',
            'manage-users',
            'manage-settings',
            'view_dashboard',

            // Client / Frontend (Zoffline)
            'trade-in',
            'sell-phone',
            'warranty-activation',
            'view-riwayat-kasir'
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
            'view-riwayat-kasir',
            'sell-phone',
            'trade-in',
            'warranty-activation',
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
            'manage-inbound',
            'manage-qc-inspections',
            'manage-qc-templates',
            'view-reporting',
            'reporting-dashboard',
            'reporting-sales',
            'reporting-products',
            'reporting-staff',
            'manage-trade-in',
            'manage-buyback',
            'view_dashboard'
        ]);

        // CS gets chat, promo (optional), and stock view
        $cs->syncPermissions([
            'access-cs-chat',
            'view-stock',
            'manage-orders',
            'manage-promos'
        ]);

        // FL (Frontliner) gets POS, Stock, and basic sales
        $fl->syncPermissions([
            'view-pos',
            'view-riwayat-kasir',
            'view-stock',
            'manage-orders',
            'sell-phone',
            'trade-in',
            'warranty-activation'
        ]);

        $this->command->info('Base permissions seeded and assigned to roles successfully.');
    }
}
