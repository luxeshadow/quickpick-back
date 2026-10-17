<?php

namespace App\Jobs;

use App\Enums\EventMediaStatusEnum;
use App\Enums\PhotoProcessingStatusEnum;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class FinalizeEventMediaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $eventId)
    {
        $this->onQueue('face-processing');
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $event = Event::query()->whereKey($this->eventId)->lockForUpdate()->first();
            if (! $event || $event->uploaded_media_count < $event->expected_media_count) {
                return;
            }

            $processed = $event->media()->whereIn('processing_status', [
                PhotoProcessingStatusEnum::PROCESSED->value,
                PhotoProcessingStatusEnum::SKIPPED->value,
            ])->count();
            $failed = $event->media()->where('processing_status', PhotoProcessingStatusEnum::FAILED->value)->count();
            if ($processed + $failed < $event->expected_media_count) {
                return;
            }

            $status = match (true) {
                $processed === 0 => EventMediaStatusEnum::FAILED,
                $failed > 0 => EventMediaStatusEnum::PARTIAL_FAILURE,
                default => EventMediaStatusEnum::COMPLETED,
            };
            $event->update([
                'media_status' => $status->value,
                'processed_media_count' => $processed,
                'failed_media_count' => $failed,
                'processing_completed_at' => now(),
            ]);
        });
    }
}
