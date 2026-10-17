<?php

namespace Tests\Feature;

use App\Enums\UserRolesEnum;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionPlanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_subscription_plans(): void
    {
        $admin = $this->user('admin@example.com');
        Role::create(['name' => UserRolesEnum::ADMIN->value, 'guard_name' => 'web']);
        $admin->assignRole(UserRolesEnum::ADMIN->value);
        Sanctum::actingAs($admin);

        $id = $this->postJson('/api/subscription-plans', $this->planData())
            ->assertCreated()
            ->json('subscription_plan.id');

        $this->getJson('/api/subscription-plans')->assertOk()->assertJsonCount(1, 'subscription_plans');
        $this->getJson("/api/subscription-plans/{$id}")->assertOk();
        $this->patchJson("/api/subscription-plans/{$id}", ['price' => 39.99])
            ->assertOk()
            ->assertJsonPath('subscription_plan.price', '39.99');
        $this->deleteJson("/api/subscription-plans/{$id}")->assertOk();
        $this->assertSoftDeleted('subscription_plans', ['id' => $id]);
        $this->getJson('/api/subscription-plans')->assertOk()->assertJsonCount(0, 'subscription_plans');
        $this->getJson("/api/subscription-plans/{$id}")->assertNotFound();
        $this->patchJson("/api/subscription-plans/{$id}", ['price' => 1])->assertNotFound();
    }

    public function test_deleted_plan_is_kept_for_existing_subscriptions(): void
    {
        $user = $this->user('subscriber@example.com');
        $plan = SubscriptionPlan::create($this->planData());
        $subscription = \App\Models\Subscription::create([
            'user_id' => $user->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);
        $plan->delete();
        $this->assertSame($plan->id, $subscription->fresh()->subscriptionPlan->id);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'active']);
    }

    public function test_non_admin_cannot_mutate_subscription_plans(): void
    {
        $user = $this->user('user@example.com');
        Sanctum::actingAs($user);
        $plan = SubscriptionPlan::create($this->planData());

        $this->postJson('/api/subscription-plans', $this->planData('Premium'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous devez être administrateur pour créer un plan d’abonnement.');
        $this->patchJson("/api/subscription-plans/{$plan->id}", ['price' => 1])
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous devez être administrateur pour modifier un plan d’abonnement.');
        $this->deleteJson("/api/subscription-plans/{$plan->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous devez être administrateur pour supprimer un plan d’abonnement.');
        $this->getJson('/api/subscription-plans')->assertOk();
    }

    private function planData(string $name = 'Pro'): array
    {
        return ['name' => $name, 'price' => 29.99, 'duration_days' => 30];
    }

    private function user(string $email): User
    {
        return User::create(['full_name' => 'Test User', 'email' => $email, 'password' => 'password']);
    }
}
