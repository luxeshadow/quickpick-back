<?php

namespace Tests\Feature;

use App\Contracts\Storage\MediaStorage;
use App\Models\AccessLink;
use App\Models\Event;
use App\Models\Media;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestScanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['face_service.hmac_secret' => str_repeat('x', 32), 'face_service.url' => 'http://face.test']);
        $this->app->instance(MediaStorage::class, new class implements MediaStorage {
            public function createSignedUploadUrl(string $path): array { return []; }
            public function uploadToSignedUrl(string $signedUrl, string $localPath, string $mimeType): void {}
            public function createSignedDownloadUrl(string $path, int $expiresIn = 3600): string { return 'https://storage.test/'.$path; }
            public function objectInfo(string $path): array { return []; }
        });
    }

    private function link(): AccessLink
    {
        $user = User::create(['full_name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'password']);
        $plan = SubscriptionPlan::create(['name' => 'Test', 'price' => 0, 'is_active' => true]);
        Subscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Event', 'status' => 'active']);
        return AccessLink::create(['event_id' => $event->id, 'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDay(), 'is_active' => true, 'max_scans' => 1]);
    }

    private function selfie(): UploadedFile
    {
        // A real PNG avoids requiring GD on Windows merely to build a fixture.
        return UploadedFile::fake()->createWithContent('selfie.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aO4sAAAAASUVORK5CYII='
        ));
    }

    public function test_matches_are_signed_scoped_and_persisted_and_quota_is_enforced(): void
    {
        $link = $this->link();
        $attributes = ['event_id' => $link->event_id, 'media_type' => 'image', 'storage_path' => 'photo.jpg',
            'original_filename' => 'photo.jpg', 'uploaded_at' => now(), 'processing_status' => 'processed',
            'model_version' => config('face_service.model_version')];
        $photo = Media::create($attributes);
        $video = Media::create([...$attributes, 'media_type' => 'video']);
        $other = Event::create(['user_id' => $link->event->user_id, 'name' => 'Other']);
        $foreign = Media::create([...$attributes, 'event_id' => $other->id]);
        Http::fake(['face.test/*' => Http::response(['event_id' => $link->event_id,
            'model_version' => config('face_service.model_version'), 'matches' => [
                ['photo_id' => $photo->id, 'face_index' => 0, 'similarity' => 0.9],
                ['photo_id' => $video->id, 'face_index' => 0, 'similarity' => 0.8],
                ['photo_id' => $foreign->id, 'face_index' => 0, 'similarity' => 0.7],
            ]])]);
        $url = '/api/public/access-links/'.$link->token.'/guest-scans';
        $this->postJson($url, ['selfie' => $this->selfie(), 'consent' => '1'])
            ->assertOk()->assertJsonPath('scan.status', 'matched')->assertJsonCount(1, 'media')
            ->assertJsonPath('media.0.id', $photo->id);
        Http::assertSent(function ($request) use ($link) {
            $timestamp = $request->header('X-QuickPick-Timestamp')[0];
            return $request['event_id'] === $link->event_id && $request->header('X-QuickPick-Signature')[0]
                === 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), str_repeat('x', 32));
        });
        $this->assertDatabaseCount('guest_scan_matches', 1);
        $this->postJson($url, ['selfie' => $this->selfie(), 'consent' => '1'])->assertStatus(410);
        Http::assertSentCount(1);
    }

    public function test_no_match_and_validation_and_service_failure(): void
    {
        $link = $this->link();
        $url = '/api/public/access-links/'.$link->token.'/guest-scans';
        Http::fake(['*' => Http::sequence()->push(['event_id' => $link->event_id,
            'model_version' => config('face_service.model_version'), 'matches' => []])
            ->push(['detail' => 'unavailable'], 503)]);
        $this->postJson($url, ['selfie' => $this->selfie()])->assertUnprocessable();
        Http::assertNothingSent();
        $this->postJson($url, ['selfie' => $this->selfie(), 'consent' => '1'])
            ->assertOk()->assertJsonPath('scan.status', 'no_match')->assertJsonCount(0, 'media');
        $link->update(['max_scans' => null]);
        $this->postJson($url, ['selfie' => $this->selfie(), 'consent' => '1'])->assertStatus(503);
        $this->assertDatabaseHas('guest_scans', ['status' => 'failed']);
    }

    public function test_invalid_link_never_calls_microservice(): void
    {
        Http::fake();
        $this->postJson('/api/public/access-links/'.str_repeat('a', 64).'/guest-scans',
            ['selfie' => $this->selfie(), 'consent' => '1'])->assertStatus(410);
        Http::assertNothingSent();
    }
}
