<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_return_three_latest_events_and_months_are_zero_filled(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::create(['full_name' => 'Admin', 'email' => 'admin@stats.test']);
        $admin->assignRole('admin');
        $user = User::create(['full_name' => 'Owner', 'email' => 'owner@stats.test']);
        $event = $this->event($user, 'One', '2026-01-01 00:00:00');
        $second = $this->event($user, 'Two', '2026-12-31 23:59:59');
        $outside = $this->event($user, 'Outside', '2027-01-01 00:00:00');
        $fallback = $this->event($user, 'Fallback', null);
        $fallback->forceFill(['created_at' => '2026-01-15 12:00:00'])->save();
        $archived = $this->event($user, 'Archived', '2026-01-10');
        $archived->delete();
        $this->event($admin, 'Other owner', '2026-01-10');
        foreach (['image', 'image', 'video'] as $type) {
            Media::create(['event_id' => $event->id, 'media_type' => $type, 'storage_path' => uniqid(),
                'original_filename' => 'test', 'uploaded_at' => now()]);
        }
        Media::create(['event_id' => $event->id, 'media_type' => 'image', 'storage_path' => 'pending', 'original_filename' => 'pending']);
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/users/me/stats?year=2026&user_id='.$admin->id)
            ->assertJsonPath('user_id', $user->id)
            ->assertOk()->assertJsonCount(3, 'events')->assertJsonMissingPath('pagination')
            ->assertJsonPath('events.0.id', $outside->id)
            ->assertJsonPath('events.1.id', $second->id)
            ->assertJsonPath('events.2.id', $fallback->id)
            ->assertJsonPath('statistics', ['events' => 4, 'photos' => 2, 'videos' => 1, 'total_media' => 3])
            ->assertJsonPath('events_by_month.year', 2026)->assertJsonPath('events_by_month.total', 3)
            ->assertJsonCount(12, 'events_by_month.labels')
            ->assertJsonPath('events_by_month.datasets.0.data', [2,0,0,0,0,0,0,0,0,0,0,1]);
        $this->getJson('/api/users/me/stats?year=2027')
            ->assertOk()->assertJsonPath('events_by_month.total', 1)->assertJsonPath('statistics.events', 4);
        $this->getJson('/api/users/me/stats?year=2025')
            ->assertOk()->assertJsonPath('events_by_month.datasets.0.data', array_fill(0, 12, 0));
    }

    public function test_stats_require_authentication_and_validate_filters_without_admin_role(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::create(['full_name' => 'User', 'email' => 'user@stats.test']);
        $url = '/api/users/me/stats';
        $this->getJson($url)->assertUnauthorized();
        Sanctum::actingAs($user);
        $this->getJson($url.'?year=invalid')->assertUnprocessable();
        $this->getJson($url)->assertOk()->assertJsonPath('events_by_month.year', now()->year);
        $this->getJson('/api/users/999999/stats')->assertNotFound();
        $this->getJson('/api/users/'.$user->id.'/stats')->assertNotFound();
    }

    private function event(User $user, string $name, ?string $date): Event
    {
        return Event::create(['user_id' => $user->id, 'name' => $name, 'event_date' => $date]);
    }
}
