<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_and_cancel_own_subscription(): void
    {
        $user = $this->user('owner@example.com');
        $plan = $this->plan();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/subscriptions', ['subscription_plan_id' => $plan->id])
            ->assertCreated()
            ->assertJsonPath('subscription.user_id', $user->id)
            ->assertJsonPath('subscription.status', 'active')
            ->json('subscription.id');

        $this->getJson('/api/subscriptions')->assertOk()->assertJsonCount(1, 'subscriptions');
        $this->deleteJson("/api/subscriptions/{$id}")
            ->assertOk()
            ->assertJsonPath('subscription.status', 'cancelled');
        $this->assertDatabaseHas('subscriptions', ['id' => $id, 'status' => 'cancelled']);
    }

    public function test_user_cannot_subscribe_while_another_subscription_is_current(): void
    {
        $user = $this->user('owner@example.com');
        $firstPlan = $this->plan();
        $secondPlan = SubscriptionPlan::create([
            'name' => 'Premium',
            'price' => 59.99,
            'duration_days' => 30,
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $firstPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/subscriptions', ['subscription_plan_id' => $secondPlan->id])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.subscription_plan_id.0',
                'Vous avez déjà un abonnement en cours. Vous devez l’annuler ou attendre son expiration avant d’en souscrire un autre.'
            );

        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_user_cannot_manage_another_users_subscription(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other@example.com');
        $subscription = Subscription::create([
            'user_id' => $owner->id,
            'subscription_plan_id' => $this->plan()->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
        Sanctum::actingAs($other);

        $this->getJson("/api/subscriptions/{$subscription->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à consulter cet abonnement.');
        $this->patchJson("/api/subscriptions/{$subscription->id}", ['status' => 'expired'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous devez être administrateur pour modifier un abonnement.');
        $this->deleteJson("/api/subscriptions/{$subscription->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à annuler cet abonnement.');
        $this->getJson('/api/subscriptions')->assertOk()->assertJsonCount(0, 'subscriptions');
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro',
            'price' => 29.99,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    private function user(string $email): User
    {
        return User::create(['full_name' => 'Test User', 'email' => $email, 'password' => 'password']);
    }
}
