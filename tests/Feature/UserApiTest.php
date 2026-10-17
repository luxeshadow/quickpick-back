<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use App\Models\Media;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Payment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'camille@example.com', bool $admin = false): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::create(['full_name' => 'Camille Martin', 'email' => $email, 'password' => 'password']);
        $user->assignRole($admin ? 'admin' : 'photographer');
        return $user;
    }

    public function test_profile_is_owner_only_and_cannot_change_privileges(): void
    {
        $user = $this->user();
        $other = $this->user('other@example.com');
        $this->patchJson('/api/users/me', ['full_name' => 'Test'])->assertUnauthorized();
        Sanctum::actingAs($user);
        $this->patchJson('/api/users/me', ['full_name' => 'Nouveau nom', 'phone' => '+22812345678'])
            ->assertOk()->assertJsonPath('user.full_name', 'Nouveau nom')->assertJsonMissingPath('user.password');
        $this->patchJson('/api/users/me', ['roles' => ['admin'], 'status' => 'active'])->assertUnprocessable();
        $this->patchJson('/api/users/me', ['email' => $other->email])->assertUnprocessable();
        $user->update(['email_verified_at' => now()]);
        $this->patchJson('/api/users/me', ['email' => 'new@example.com'])
            ->assertOk()->assertJsonPath('user.email_verified_at', null);
        $this->assertFalse($user->can('update', $other));
        $this->getJson('/api/users')->assertForbidden();
        $this->getJson('/api/users/search?search=Camille')->assertForbidden();
        $this->getJson('/api/users/'.$other->id)->assertForbidden();
        $this->deleteJson('/api/users/'.$other->id)->assertForbidden();
    }

    public function test_admin_search_detail_subscription_payments_and_counts(): void
    {
        $admin = $this->user('admin@example.com', true);
        $target = $this->user('camille.martin@example.com');
        $plan = SubscriptionPlan::create(['name' => 'Premium', 'price' => 5000, 'is_active' => true]);
        $subscription = Subscription::create(['user_id' => $target->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        Payment::create(['user_id' => $target->id, 'subscription_id' => $subscription->id,
            'provider' => 'test', 'payment_method' => 'card', 'amount' => 5000, 'currency' => 'XOF', 'status' => 'paid']);
        $event = Event::create(['user_id' => $target->id, 'name' => 'Mariage']);
        foreach (['image', 'image', 'video'] as $type) {
            Media::create(['event_id' => $event->id, 'media_type' => $type, 'storage_path' => uniqid(),
                'original_filename' => 'test.jpg', 'uploaded_at' => now()]);
        }
        Media::create(['event_id' => $event->id, 'media_type' => 'image', 'storage_path' => 'pending', 'original_filename' => 'pending.jpg']);
        Sanctum::actingAs($admin);
        $this->getJson('/api/users?search=CAMILLE.MARTIN')->assertOk()->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.statistics', ['events' => 1, 'photos' => 2, 'videos' => 1, 'total_media' => 3])
            ->assertJsonPath('users.0.current_subscription.subscription_plan.name', 'Premium');
        $this->getJson('/api/users/search?search=not-found')->assertOk()->assertJsonCount(0, 'users');
        $this->getJson('/api/users/'.$target->id)->assertOk()
            ->assertJsonPath('user.statistics.total_media', 3)->assertJsonPath('user.events.0.photos_count', 2)
            ->assertJsonCount(1, 'user.payments')->assertJsonCount(1, 'user.subscriptions')
            ->assertJsonMissingPath('user.password')->assertJsonMissingPath('user.google_id');
        $token = $target->createToken('test');
        $this->deleteJson('/api/users/'.$target->id)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_expired_subscriptions_and_archived_events_are_not_current_totals(): void
    {
        $admin = $this->user('admin@example.com', true);
        $target = $this->user();
        $plan = SubscriptionPlan::create(['name' => 'Expired', 'price' => 0, 'is_active' => true]);
        Subscription::create(['user_id' => $target->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);
        $event = Event::create(['user_id' => $target->id, 'name' => 'Archived']);
        Media::create(['event_id' => $event->id, 'media_type' => 'image', 'storage_path' => 'archived.jpg',
            'original_filename' => 'archived.jpg', 'uploaded_at' => now()]);
        $event->delete();
        Sanctum::actingAs($admin);
        $this->getJson('/api/users?search=camille@example.com')->assertOk()
            ->assertJsonPath('users.0.current_subscription', null)
            ->assertJsonPath('users.0.statistics.total_media', 0);
        $this->getJson('/api/users/'.$target->id)->assertOk()
            ->assertJsonPath('user.current_subscription', null)->assertJsonCount(0, 'user.events');
        $this->getJson('/api/users?search=%25')->assertOk()->assertJsonCount(0, 'users');
    }
}
