<?php

namespace Tests\Feature;

use App\Models\AccessLink;
use App\Models\Event;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_subscription_is_detected_and_existing_links_are_disabled(): void
    {
        [$user, $subscription] = $this->expiredUser();
        $event = Event::create(['user_id' => $user->id, 'name' => 'Événement existant']);
        $link = AccessLink::create([
            'event_id' => $event->id,
            'created_by' => $user->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/subscriptions/current')
            ->assertOk()
            ->assertJsonPath('has_active_subscription', false)
            ->assertJsonPath('subscription', null);

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'expired']);
        $this->assertDatabaseHas('access_links', ['id' => $link->id, 'is_active' => false]);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
        $this->getJson("/api/public/access-links/{$link->token}")->assertStatus(410);
    }

    public function test_expired_user_cannot_create_event_or_access_link(): void
    {
        [$user] = $this->expiredUser();
        $event = Event::create(['user_id' => $user->id, 'name' => 'Événement conservé']);
        Sanctum::actingAs($user);

        $this->postJson('/api/events', ['name' => 'Nouvel événement'])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.event.0',
                'Votre abonnement est expiré ou inactif. Renouvelez-le pour effectuer cette action.'
            );

        $this->postJson("/api/events/{$event->id}/access-links")
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.access_link.0',
                'Votre abonnement est expiré ou inactif. Renouvelez-le pour effectuer cette action.'
            );
    }

    private function expiredUser(): array
    {
        $user = User::create([
            'full_name' => 'Expired User',
            'email' => 'expired@example.com',
            'password' => 'password',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Expired Plan',
            'price' => 0,
            'duration_days' => 30,
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subMinute(),
        ]);

        return [$user, $subscription];
    }
}
