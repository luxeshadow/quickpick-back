<?php

namespace App\Actions\PublicGallery;

use App\Actions\AccessLink\ResolvePublicAccessLinkAction;
use App\Contracts\Storage\MediaStorage;
use App\Enums\MediaTypeEnum;
use Illuminate\Validation\ValidationException;

class DownloadSelectedMediaAction
{
    public function __construct(
        private readonly ResolvePublicAccessLinkAction $resolveAccessLink,
        private readonly MediaStorage $storage,
    ) {}

    /** @param array<int, int> $mediaIds */
    public function execute(string $token, array $mediaIds): array
    {
        $accessLink = $this->resolveAccessLink->execute($token);

        $media = $accessLink->event->media()
            ->whereIn('id', $mediaIds)
            ->where('media_type', MediaTypeEnum::IMAGE->value)
            ->whereNotNull('uploaded_at')
            ->get();

        if ($media->count() !== count($mediaIds)) {
            throw ValidationException::withMessages([
                'media_ids' => ['Un ou plusieurs médias sélectionnés n’appartiennent pas à cet événement.'],
            ]);
        }

        return $media->map(fn ($item): array => [
                'id' => $item->id,
                'filename' => $item->original_filename,
                'download_url' => $this->storage->createSignedDownloadUrl($item->storage_path),
            ])->values()->all();
    }
}


