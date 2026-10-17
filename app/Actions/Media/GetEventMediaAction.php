<?php

namespace App\Actions\Media;

use App\Enums\MediaTypeEnum;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class GetEventMediaAction
{
    /** @return Collection<int, \App\Models\Media> */
    public function execute(Event $event, ?string $type = null): Collection
    {
        return $event->media()
            ->when($type, fn ($query) => $query->where('media_type', MediaTypeEnum::from($type)->value))
            ->latest()
            ->get();
    }
}
