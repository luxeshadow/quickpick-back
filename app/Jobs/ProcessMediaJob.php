<?php

namespace App\Jobs;

use App\Enums\MediaTypeEnum;
use App\Enums\PhotoProcessingStatusEnum;
use App\Models\Media;
use App\Services\Face\FaceServiceClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class ProcessMediaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 45;
    public string $jobId;

    public function __construct(
        public readonly int $mediaId,
        ?string $jobId = null,
    ) {
        $this->jobId = $jobId ?: (string) Str::uuid();
        $this->onQueue('face-processing');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 60, 120, 300];
    }

    public function handle(FaceServiceClient $client): void
    {
        $medium = Media::query()->find($this->mediaId);
        if (! $medium || $medium->media_type !== MediaTypeEnum::IMAGE || $medium->uploaded_at === null) {
            return;
        }
        if ($medium->processing_status === PhotoProcessingStatusEnum::PROCESSED) {
            return;
        }

        $attempt = $this->attempts();
        $medium->update([
            'processing_status' => PhotoProcessingStatusEnum::PROCESSING->value,
            'processing_job_id' => $this->jobId,
            'processing_attempt' => $attempt,
            'model_version' => config('face_service.model_version'),
            'processing_started_at' => $medium->processing_started_at ?? now(),
            'processing_error' => null,
        ]);

        $client->processPhoto($medium->refresh(), $this->jobId, $attempt);
    }

    public function failed(?Throwable $exception): void
    {
        $medium = Media::query()->find($this->mediaId);
        if (! $medium || $medium->processing_status === PhotoProcessingStatusEnum::PROCESSED) {
            return;
        }
        $medium->update([
            'processing_status' => PhotoProcessingStatusEnum::FAILED->value,
            'processing_error' => mb_substr($exception?->getMessage() ?? 'Échec du traitement facial.', 0, 2000),
            'processed_at' => now(),
        ]);
        FinalizeEventMediaJob::dispatch($medium->event_id);
    }
}


