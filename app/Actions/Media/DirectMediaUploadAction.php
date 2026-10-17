<?php

namespace App\Actions\Media;

use App\Contracts\Storage\MediaStorage;
use App\Enums\EventMediaStatusEnum;
use App\Models\Event;
use App\Models\Media;
use App\Models\UploadBatchItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

class DirectMediaUploadAction
{
    public function __construct(
        private readonly PrepareEventMediaUploadAction $prepare,
        private readonly ConfirmEventMediaUploadAction $confirm,
        private readonly MediaStorage $storage,
    ) {}

    /** @param array<int, UploadedFile> $files */
    public function execute(User $user, Event $event, array $files): array
    {
        $metadata = collect($files)->map(fn (UploadedFile $file): array => [
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getMimeType(),
            'file_size' => $file->getSize(),
        ])->all();
        $prepared = $this->prepare->execute($user, $event, $metadata);

        $successful = [];
        $failed = [];

        foreach ($prepared['uploads'] as $index => $upload) {
            try {
                $this->storage->uploadToSignedUrl(
                    $upload['signed_url'],
                    $files[$index]->getRealPath(),
                    $upload['mime_type'],
                );
                UploadBatchItem::whereKey($upload['item_id'])->update(['status' => 'uploaded']);
                $successful[] = $upload;
            } catch (Throwable $exception) {
                UploadBatchItem::whereKey($upload['item_id'])->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
                $failed[] = [
                    'filename' => $upload['filename'],
                    'error' => 'Le fichier n’a pas pu être envoyé.',
                ];
                Media::whereKey($upload['media_id'])->delete();
            }
        }

        if ($successful !== []) {
            $event = $this->confirm->execute(
                $prepared['event'],
                collect($successful)->pluck('media_id')->all(),
            );
        }

        $prepared['batch']->update([
            'status' => $failed === [] ? 'completed' : ($successful === [] ? 'failed' : 'partial_failure'),
            'uploaded_count' => count($successful),
            'failed_count' => count($failed),
        ]);

        if ($failed !== []) {
            $event = Event::findOrFail($event->id);
            $event->update([
                'expected_media_count' => $event->media()->count(),
                'uploaded_media_count' => $event->media()->whereNotNull('uploaded_at')->count(),
                'failed_media_count' => count($failed),
                'media_status' => $successful === []
                    ? EventMediaStatusEnum::FAILED->value
                    : EventMediaStatusEnum::PARTIAL_FAILURE->value,
            ]);
        }

        return [
            'event' => $event->fresh()->load('media'),
            'batch' => $prepared['batch']->fresh(),
            'uploaded_files' => collect($successful)->pluck('filename')->values()->all(),
            'failed_files' => $failed,
        ];
    }
}
