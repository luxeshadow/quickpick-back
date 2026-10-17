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

class AccessLinkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_list_update_resolve_and_hard_delete_access_link(): void
    {
        $user = $this->user('owner@example.com');
        $this->subscribe($user);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Mariage']);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/events/{$event->id}/access-links", ['max_scans' => 10])
            ->assertCreated()
            ->assertJsonPath('access_link.source', 'manual')
            ->assertJsonPath('access_link.max_scans', 10);

        $id = $response->json('access_link.id');
        $token = $response->json('access_link.token');

        $this->getJson("/api/events/{$event->id}/access-links")
            ->assertOk()
            ->assertJsonCount(1, 'access_links');
        $this->patchJson("/api/access-links/{$id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('access_link.is_active', false);
        $this->getJson("/api/public/access-links/{$token}")->assertStatus(410);
        $this->patchJson("/api/access-links/{$id}", ['is_active' => true])->assertOk();
        $this->getJson("/api/public/access-links/{$token}")
            ->assertOk()
            ->assertJsonPath('access_link.event.name', 'Mariage');

        $this->deleteJson("/api/access-links/{$id}")->assertOk();
        $this->assertDatabaseMissing('access_links', ['id' => $id]);
    }

    public function test_user_cannot_manage_another_users_access_link(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other@example.com');
        $event = Event::create(['user_id' => $owner->id, 'name' => 'Privé']);
        $link = AccessLink::create([
            'event_id' => $event->id,
            'created_by' => $owner->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMonth(),
        ]);
        Sanctum::actingAs($other);

        $this->getJson("/api/access-links/{$link->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à consulter ce lien d’accès.');
        $this->deleteJson("/api/access-links/{$link->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à supprimer ce lien d’accès.');
    }

    private function user(string $email): User
    {
        return User::create(['full_name' => 'Test User', 'email' => $email, 'password' => 'password']);
    }

    private function subscribe(User $user): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Plan '.$user->id,
            'price' => 0,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
