<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Add roles if not exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cs', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'fl', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@zed.com'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password'), // or any specific default
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        $this->command->info("Role 'admin' ensure created and assigned to admin@zed.com");
    }
}
