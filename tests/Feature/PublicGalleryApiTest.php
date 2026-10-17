<?php

namespace Tests\Feature;

use App\Contracts\Storage\MediaStorage;
use App\Jobs\SendManualMediaDeliveryJob;
use App\Models\AccessLink;
use App\Models\Event;
use App\Models\Media;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicGalleryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->app->instance(MediaStorage::class, new class implements MediaStorage {
            public function uploadToSignedUrl(string $signedUrl, string $localPath, string $mimeType): void {}

            public function createSignedDownloadUrl(string $path, int $expiresIn = 3600): string
            {
                return "https://storage.test/{$path}?expires={$expiresIn}";
            }
            public function createSignedUploadUrl(string $path): array
            {
                return ['signed_url' => '', 'token' => '', 'path' => $path];
            }
            public function objectInfo(string $path): array { return []; }
        });
    }

    public function test_guest_can_browse_download_and_send_selected_photos_without_guest_scan(): void
    {
        [$event, $link] = $this->gallery();
        $first = $this->photo($event, 'one.jpg');
        $second = $this->photo($event, 'two.jpg');

        $this->getJson("/api/public/access-links/{$link->token}/media?per_page=10")
            ->assertOk()
            ->assertJsonCount(2, 'media')
            ->assertJsonStructure(['media', 'pagination' => ['next_cursor', 'has_more']]);

        $this->postJson("/api/public/access-links/{$link->token}/downloads", [
            'media_ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonCount(2, 'downloads');

        $deliveryId = $this->postJson("/api/public/access-links/{$link->token}/deliveries", [
            'media_ids' => [$first->id, $second->id],
            'channel' => 'email',
            'recipient' => 'guest@example.com',
        ])->assertStatus(202)->json('delivery.id');

        $this->assertDatabaseHas('media_deliveries', [
            'id' => $deliveryId,
            'guest_scan_id' => null,
            'access_link_id' => $link->id,
        ]);
        $this->assertDatabaseCount('guest_scans', 0);
        $this->assertDatabaseCount('media_delivery_items', 2);
        Queue::assertPushed(SendManualMediaDeliveryJob::class);
    }

    private function gallery(): array
    {
        $user = User::create(['full_name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'password']);
        $plan = SubscriptionPlan::create(['name' => 'Gallery', 'price' => 0, 'duration_days' => 30, 'is_active' => true]);
        Subscription::create([
            'user_id' => $user->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Mariage']);
        $link = AccessLink::create([
            'event_id' => $event->id, 'created_by' => $user->id,
            'token' => bin2hex(random_bytes(32)), 'expires_at' => now()->addMonth(),
        ]);

        return [$event, $link];
    }

    private function photo(Event $event, string $name): Media
    {
        return Media::create([
            'event_id' => $event->id,
            'media_type' => 'image',
            'storage_path' => "events/{$event->id}/images/{$name}",
            'original_filename' => $name,
            'mime_type' => 'image/jpeg',
            'uploaded_at' => now(),
        ]);
    }
}



