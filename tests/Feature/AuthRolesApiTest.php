<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Google\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthRolesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_otp_returns_role_names_and_hides_password(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::create(['full_name' => 'Photographer', 'email' => 'photo@example.com', 'password' => 'password']);
        $user->assignRole('photographer');
        Cache::put('otp:user:'.$user->email, 123456, now()->addMinutes(10));
        $this->postJson('/api/auth/verify-otp', ['email' => $user->email, 'otp' => '123456'])
            ->assertOk()->assertJsonPath('user.roles', ['photographer'])
            ->assertJsonStructure(['token'])->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_google_registration_returns_photographer_role(): void
    {
        $this->seed(RoleSeeder::class);
        $this->mock(Client::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')->once()->with('google-test-token')
                ->andReturn(['email' => 'new@example.com', 'name' => 'New User', 'sub' => 'google-123']);
        });
        $this->postJson('/api/auth/login/google', ['token' => 'google-test-token'])
            ->assertOk()->assertJsonPath('user.roles', ['photographer'])
            ->assertJsonStructure(['token'])->assertJsonMissingPath('user.password');
    }

    public function test_google_login_preserves_existing_admin_role(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::create(['full_name' => 'Admin', 'email' => 'admin@example.com', 'status' => 'active']);
        $user->assignRole('admin');
        $this->mock(Client::class, function ($mock) use ($user) {
            $mock->shouldReceive('verifyIdToken')->once()
                ->andReturn(['email' => $user->email, 'name' => 'Admin', 'sub' => 'google-admin']);
        });
        $this->postJson('/api/auth/login/google', ['token' => 'google-test-token'])
            ->assertOk()->assertJsonPath('user.roles', ['admin'])->assertJsonPath('user.id', $user->id);
        $this->assertDatabaseCount('users', 1);
    }
}
