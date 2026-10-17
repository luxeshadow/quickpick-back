<?php

namespace App\Actions\PublicGallery;

use App\Actions\AccessLink\ResolvePublicAccessLinkAction;
use App\Contracts\Storage\MediaStorage;
use App\Enums\MediaTypeEnum;
use Illuminate\Pagination\CursorPaginator;

class BrowseEventMediaAction
{
    public function __construct(
        private readonly ResolvePublicAccessLinkAction $resolveAccessLink,
        private readonly MediaStorage $storage,
    ) {}

    public function execute(string $token, int $perPage = 30): CursorPaginator
    {
        $accessLink = $this->resolveAccessLink->execute($token);
        $paginator = $accessLink->event->media()
            ->where('media_type', MediaTypeEnum::IMAGE->value)
            ->whereNotNull('uploaded_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $paginator->through(fn ($medium): array => [
            'id' => $medium->id,
            'filename' => $medium->original_filename,
            'width' => $medium->width,
            'height' => $medium->height,
            'url' => $this->storage->createSignedDownloadUrl($medium->storage_path),
        ]);

        return $paginator;
    }
}


