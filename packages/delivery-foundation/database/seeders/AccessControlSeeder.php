<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Database\Seeders;

use GazaDelivery\Foundation\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            StaffRole::Operations->value => ['stores.view', 'stores.review', 'staff.view', 'orders.view', 'orders.assign'],
            StaffRole::Finance->value => ['stores.view', 'finance.view', 'finance.remittances.approve', 'finance.settlements.approve', 'pricing.manage'],
            StaffRole::Captain->value => ['orders.view_assigned', 'orders.update_assigned', 'remittances.submit_own'],
        ];

        $all = array_values(array_unique(array_merge(...array_values($roles))));
        $all = array_merge($all, ['staff.manage', 'roles.manage', 'audit.view']);

        foreach ($all as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }

        $roles[StaffRole::SuperAdmin->value] = $all;

        foreach ($roles as $name => $permissions) {
            Role::findOrCreate($name, 'staff')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
