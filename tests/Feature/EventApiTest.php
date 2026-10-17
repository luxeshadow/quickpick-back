<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_request_returns_json_401(): void
    {
        $this->postJson('/api/events', ['name' => 'Événement'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_user_can_create_list_show_update_and_delete_own_event(): void
    {
        $user = $this->user('owner@example.com');
        $this->subscribe($user);
        Sanctum::actingAs($user);

        $eventId = $this->postJson('/api/events', [
            'name' => 'Mon événement',
            'location' => 'Londres',
        ])->assertCreated()
            ->assertJsonPath('event.user_id', $user->id)
            ->json('event.id');

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(1, 'events');

        $this->getJson("/api/events/{$eventId}")
            ->assertOk()
            ->assertJsonPath('event.name', 'Mon événement');

        $this->patchJson("/api/events/{$eventId}", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('event.status', 'inactive')
            ->assertJsonPath('event.name', 'Mon événement');

        $this->deleteJson("/api/events/{$eventId}")->assertOk();
        $this->assertSoftDeleted('events', ['id' => $eventId]);
    }

    public function test_user_cannot_access_another_users_event(): void
    {
        $owner = $this->user('owner@example.com');
        $otherUser = $this->user('other@example.com');
        $event = Event::create(['user_id' => $owner->id, 'name' => 'Privé']);

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/events/{$event->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à consulter cet événement.');
        $this->patchJson("/api/events/{$event->id}", ['status' => 'inactive'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à modifier cet événement.');
        $this->deleteJson("/api/events/{$event->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à archiver cet événement.');
        $this->getJson('/api/events')->assertOk()->assertJsonCount(0, 'events');
    }

    public function test_user_cannot_create_two_events_with_the_same_name(): void
    {
        $user = $this->user('duplicate@example.com');
        $this->subscribe($user);
        Event::create(['user_id' => $user->id, 'name' => 'Mon mariage']);
        Sanctum::actingAs($user);

        $this->postJson('/api/events', ['name' => 'Mon mariage'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Vous avez déjà un événement portant ce nom.');
    }

    private function user(string $email): User
    {
        return User::create([
            'full_name' => 'Test User',
            'email' => $email,
            'password' => 'password',
        ]);
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
