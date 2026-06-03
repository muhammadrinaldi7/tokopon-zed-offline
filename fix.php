<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

Permission::firstOrCreate(['name' => 'view-reporting', 'guard_name' => 'web']);
$role = Role::where('name', 'super-admin')->first();
if ($role) {
    $role->givePermissionTo('view-reporting');
}
echo "Permission view-reporting created and assigned to super-admin.\n";
