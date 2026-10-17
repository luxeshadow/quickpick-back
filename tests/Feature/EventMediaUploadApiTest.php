<?php

namespace Tests\Feature;

use App\Contracts\Storage\MediaStorage;
use App\Models\Event;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventMediaUploadApiTest extends TestCase
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
                return "https://storage.test/{$path}?download=test";
            }

            public function createSignedUploadUrl(string $path): array
            {
                return ['signed_url' => "https://storage.test/{$path}?token=test", 'token' => 'test', 'path' => $path];
            }

            public function objectInfo(string $path): array
            {
                return str_contains($path, '/videos/')
                    ? ['size' => 2048, 'contentType' => 'video/mp4']
                    : ['size' => 1024, 'contentType' => 'image/jpeg'];
            }
        });
    }

    public function test_each_upload_creates_a_batch_and_returns_filenames(): void
    {
        [$user, $event] = $this->createUserWithEvent(false);
        Sanctum::actingAs($user);

        $this->post('/api/events/'.$event->id.'/media', [
            'files' => [UploadedFile::fake()->image('one.jpg')],
        ])->assertCreated()
            ->assertJsonPath('upload_batch.expected_count', 1)
            ->assertJsonPath('uploaded_files.0', 'one.jpg')
            ->assertJsonCount(0, 'failed_files')
            ->assertJsonPath('event.expected_media_count', 1)
            ->assertJsonCount(1, 'media');

        $this->post('/api/events/'.$event->id.'/media', [
            'files' => [UploadedFile::fake()->image('two.jpg')],
        ])->assertCreated()
            ->assertJsonPath('upload_batch.expected_count', 1)
            ->assertJsonPath('uploaded_files.0', 'two.jpg')
            ->assertJsonPath('event.expected_media_count', 2)
            ->assertJsonCount(2, 'media');
    }

    public function test_video_is_accepted_only_when_the_plan_allows_it(): void
    {
        [$user, $event] = $this->createUserWithEvent(true);
        Sanctum::actingAs($user);

        $this->post('/api/events/'.$event->id.'/media', [
            'files' => [
                UploadedFile::fake()->image('photo.jpg'),
                UploadedFile::fake()->create('clip.mp4', 2, 'video/mp4'),
            ],
        ])->assertCreated()->assertJsonCount(2, 'media');

        $this->getJson('/api/events/'.$event->id.'/media?type=video')
            ->assertOk()
            ->assertJsonCount(1, 'media')
            ->assertJsonPath('media.0.processing_status', 'skipped');
    }

    private function createUserWithEvent(bool $acceptVideo): array
    {
        $user = User::create([
            'full_name' => 'Photographe',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => fake()->unique()->word(),
            'price' => 0,
            'duration_days' => null,
            'max_events' => 2,
            'max_photos' => 100,
            'accept_video' => $acceptVideo,
            'max_videos' => $acceptVideo ? 1 : 0,
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => null,
        ]);

        return [$user, Event::create(['user_id' => $user->id, 'name' => fake()->unique()->sentence(2)])];
    }
}
