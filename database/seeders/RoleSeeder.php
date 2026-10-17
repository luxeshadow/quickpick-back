<?php

namespace Database\Seeders;

use App\Enums\UserRolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(PermissionRegistrar $permissionRegistrar): void
    {
        $permissionRegistrar->forgetCachedPermissions();

        foreach (UserRolesEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
            ]);
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}
