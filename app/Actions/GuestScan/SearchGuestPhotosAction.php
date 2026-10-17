<?php

namespace App\Actions\GuestScan;

use App\Actions\AccessLink\ResolvePublicAccessLinkAction;
use App\Contracts\Storage\MediaStorage;
use App\Models\AccessLink;
use App\Models\GuestScan;
use App\Services\Face\FaceServiceClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Throwable;

class SearchGuestPhotosAction
{
    public function __construct(
        private readonly ResolvePublicAccessLinkAction $resolve,
        private readonly FaceServiceClient $faces,
        private readonly MediaStorage $storage,
    ) {}

    public function execute(string $token, UploadedFile $selfie): array
    {
        $link = $this->resolve->execute($token);
        $scan = DB::transaction(function () use ($link) {
            $locked = AccessLink::query()->lockForUpdate()->find($link->id);
            if (! $locked || ! $locked->is_active || $locked->expires_at->isPast()
                || ($locked->max_scans !== null && $locked->scans_count >= $locked->max_scans)) {
                throw new GoneHttpException('Ce lien ne permet plus de scanner.');
            }
            $locked->increment('scans_count');
            $locked->update(['last_used_at' => now()]);
            return GuestScan::create(['event_id' => $locked->event_id, 'access_link_id' => $locked->id, 'status' => 'processing']);
        });
        try {
            $result = $this->faces->searchFaces($link->event_id, $selfie->getContent());
            $eligible = $link->event->media()
                ->where('media_type', 'image')->where('processing_status', 'processed')
                ->where('model_version', config('face_service.model_version'))
                ->whereNotNull('uploaded_at')
                ->whereIn('id', array_column($result['matches'], 'photo_id'))->get()->keyBy('id');
            $media = [];
            $rows = [];
            foreach ($result['matches'] as $match) {
                $photo = $eligible->get($match['photo_id']);
                if (! $photo) { continue; }
                $rank = count($media) + 1;
                $rows[] = ['media_id' => $photo->id, 'face_index' => $match['face_index'], 'similarity' => $match['similarity'], 'rank' => $rank];
                $media[] = [
                    'id' => $photo->id, 'filename' => $photo->original_filename,
                    'width' => $photo->width, 'height' => $photo->height,
                    'similarity' => $match['similarity'],
                    'url' => $this->storage->createSignedDownloadUrl($photo->storage_path),
                ];
            }
            DB::transaction(function () use ($scan, $rows) {
                $scan->matches()->createMany($rows);
                $scan->update([
                    'status' => $rows ? 'matched' : 'no_match', 'matches_count' => count($rows),
                    'best_similarity' => $rows ? max(array_column($rows, 'similarity')) : null,
                ]);
            });
            return [
                'scan' => ['id' => $scan->id, 'status' => $scan->status->value, 'matches_count' => count($media)],
                'media' => $media,
            ];
        } catch (Throwable $error) {
            $scan->update(['status' => 'failed', 'error_message' => 'La recherche faciale a échoué.']);
            if ($error instanceof ValidationException && isset($error->errors()['selfie'])) { throw $error; }
            abort(503, 'La recherche faciale est temporairement indisponible.');
        }
    }
}
