<?php

namespace Tests\Feature;

use App\Enums\UserRolesEnum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_every_application_role_for_the_web_guard(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        foreach (UserRolesEnum::cases() as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role->value,
                'guard_name' => 'web',
            ]);
        }

        $this->assertSame(count(UserRolesEnum::cases()), Role::count());
    }
}
