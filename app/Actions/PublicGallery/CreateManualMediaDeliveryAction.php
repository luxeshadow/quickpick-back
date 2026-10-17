<?php

namespace App\Actions\PublicGallery;

use App\Actions\AccessLink\ResolvePublicAccessLinkAction;
use App\Enums\MediaTypeEnum;
use App\Jobs\SendManualMediaDeliveryJob;
use App\Models\MediaDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateManualMediaDeliveryAction
{
    public function __construct(private readonly ResolvePublicAccessLinkAction $resolveAccessLink) {}

    /** @param array<string, mixed> $data */
    public function execute(string $token, array $data): MediaDelivery
    {
        $accessLink = $this->resolveAccessLink->execute($token);
        $mediaIds = array_values($data['media_ids']);
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

        $delivery = DB::transaction(function () use ($accessLink, $data, $media): MediaDelivery {
            $delivery = MediaDelivery::create([
                'access_link_id' => $accessLink->id,
                'guest_scan_id' => null,
                'channel' => $data['channel'],
                'recipient' => $data['recipient'],
                'status' => 'pending',
            ]);
            $delivery->media()->sync($media->modelKeys());

            return $delivery;
        });

        SendManualMediaDeliveryJob::dispatch($delivery->id)->afterCommit();

        return $delivery->load('media:id,original_filename');
    }
}


