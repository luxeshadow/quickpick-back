<?php

namespace App\Actions\Media;

use App\Contracts\Storage\MediaStorage;
use App\Enums\EventMediaStatusEnum;
use App\Enums\MediaTypeEnum;
use App\Enums\PhotoProcessingStatusEnum;
use App\Models\Event;
use App\Models\UploadBatch;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrepareEventMediaUploadAction
{
    public function __construct(
        private readonly MediaStorage $storage,
        private readonly SubscriptionAccessService $subscriptionAccess,
    ) {}

    /** @param array<int, array<string, mixed>> $files */
    public function execute(User $user, Event $event, array $files): array
    {
        return DB::transaction(function () use ($user, $event, $files): array {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $subscription = $this->subscriptionAccess->requireActive($user, 'files');

            $plan = $subscription->subscriptionPlan;
            $imageCount = collect($files)->filter(
                fn (array $file): bool => str_starts_with($file['mime_type'], 'image/')
            )->count();
            $videoCount = count($files) - $imageCount;

            $existingImages = $event->media()->where('media_type', MediaTypeEnum::IMAGE->value)->count();
            $existingVideos = $event->media()->where('media_type', MediaTypeEnum::VIDEO->value)->count();

            if ($plan->max_photos !== null && $existingImages + $imageCount > $plan->max_photos) {
                throw ValidationException::withMessages([
                    'files' => ["Votre plan autorise au maximum {$plan->max_photos} photos par événement."],
                ]);
            }
            if ($videoCount > 0 && ! $plan->accept_video) {
                throw ValidationException::withMessages([
                    'files' => ['Votre plan d’abonnement ne permet pas l’upload de vidéos.'],
                ]);
            }
            if ($videoCount > 0 && $plan->max_videos !== null && $existingVideos + $videoCount > $plan->max_videos) {
                throw ValidationException::withMessages([
                    'files' => ["Votre plan autorise au maximum {$plan->max_videos} vidéos par événement."],
                ]);
            }

            $event->update([
                'media_status' => EventMediaStatusEnum::UPLOADING->value,
                'expected_media_count' => $event->media()->count() + count($files),
                'upload_started_at' => now(),
            ]);

            $batch = UploadBatch::create([
                'event_id' => $event->id,
                'created_by' => $user->id,
                'expected_count' => count($files),
            ]);

            $uploads = [];
            foreach ($files as $file) {
                $extension = match ($file['mime_type']) {
                    'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                    'video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm',
                };
                $mediaType = str_starts_with($file['mime_type'], 'video/')
                    ? MediaTypeEnum::VIDEO
                    : MediaTypeEnum::IMAGE;
                $directory = $mediaType === MediaTypeEnum::VIDEO ? 'videos' : 'images';
                $path = "events/{$event->id}/{$directory}/".Str::uuid().".{$extension}";
                $medium = $event->media()->create([
                    'media_type' => $mediaType->value,
                    'storage_path' => $path,
                    'original_filename' => $file['original_filename'],
                    'mime_type' => $file['mime_type'],
                    'file_size' => $file['file_size'],
                    'processing_status' => $mediaType === MediaTypeEnum::VIDEO
                        ? PhotoProcessingStatusEnum::SKIPPED->value
                        : PhotoProcessingStatusEnum::PENDING->value,
                ]);
                $signed = $this->storage->createSignedUploadUrl($path);
                $item = $batch->items()->create([
                    'media_id' => $medium->id,
                    'filename' => $file['original_filename'],
                ]);
                $uploads[] = [
                    'media_id' => $medium->id,
                    'item_id' => $item->id,
                    'filename' => $file['original_filename'],
                    'signed_url' => $signed['signed_url'],
                    'mime_type' => $file['mime_type'],
                ];
            }

            return ['event' => $event->fresh(), 'batch' => $batch, 'uploads' => $uploads];
        });
    }
}
