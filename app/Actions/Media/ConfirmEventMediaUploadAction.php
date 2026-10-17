<?php

namespace App\Actions\Media;

use App\Contracts\Storage\MediaStorage;
use App\Enums\EventMediaStatusEnum;
use App\Enums\MediaTypeEnum;
use App\Enums\PhotoProcessingStatusEnum;
use App\Jobs\FinalizeEventMediaJob;
use App\Jobs\ProcessMediaJob;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmEventMediaUploadAction
{
    public function __construct(private readonly MediaStorage $storage) {}

    /** @param array<int, int> $mediaIds */
    public function execute(Event $event, array $mediaIds): Event
    {
        $event = DB::transaction(function () use ($event, $mediaIds): Event {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            foreach ($mediaIds as $mediaId) {
                $medium = $event->media()->whereKey($mediaId)->first();
                if (! $medium) {
                    throw ValidationException::withMessages(['media' => ['Un média ne fait pas partie de cet événement.']]);
                }

                $info = $this->storage->objectInfo($medium->storage_path);
                $medium->update([
                    'file_size' => data_get($info, 'size', data_get($info, 'metadata.size', $medium->file_size)),
                    'mime_type' => data_get($info, 'contentType', data_get($info, 'metadata.mimetype', $medium->mime_type)),
                    'uploaded_at' => now(),
                    'processed_at' => $medium->media_type === MediaTypeEnum::VIDEO ? now() : null,
                ]);
            }

            $uploaded = $event->media()->whereNotNull('uploaded_at')->count();
            $processed = $event->media()->where('processing_status', PhotoProcessingStatusEnum::SKIPPED->value)->count();
            $event->update([
                'media_count' => $uploaded,
                'uploaded_media_count' => $uploaded,
                'processed_media_count' => $processed,
                'media_status' => $uploaded === $event->expected_media_count
                    ? EventMediaStatusEnum::PROCESSING->value
                    : EventMediaStatusEnum::UPLOADING->value,
                'upload_completed_at' => $uploaded === $event->expected_media_count ? now() : null,
            ]);

            return $event->fresh()->load('media');
        });

        foreach ($event->media as $medium) {
            if ($medium->media_type === MediaTypeEnum::IMAGE
                && $medium->processing_status === PhotoProcessingStatusEnum::PENDING) {
                ProcessMediaJob::dispatch($medium->id);
            }
        }
        FinalizeEventMediaJob::dispatch($event->id);

        return $event;
    }
}
