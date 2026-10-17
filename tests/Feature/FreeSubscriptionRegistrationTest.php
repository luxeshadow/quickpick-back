<?php

namespace Tests\Feature;

use App\Enums\UserRolesEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FreeSubscriptionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_traditional_registration_activates_the_free_plan(): void
    {
        Mail::fake();
        Role::create(['name' => UserRolesEnum::PHOTOGRAPHER->value, 'guard_name' => 'web']);

        $this->postJson('/api/auth/register', [
            'full_name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Gratuit',
            'price' => 0,
            'duration_days' => null,
            'max_events' => 2,
            'max_photos' => 100,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'status' => 'active',
            'ends_at' => null,
            'cancelled_at' => null,
        ]);
    }
}
