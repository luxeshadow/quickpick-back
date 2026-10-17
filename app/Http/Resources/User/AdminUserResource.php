<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;

class AdminUserResource extends UserResource
{
    public function toArray(Request $request): array
    {
        $current = $this->subscriptions->first(fn ($s) => $s->status->value === 'active'
            && $s->starts_at->lte(now()) && ($s->ends_at === null || $s->ends_at->gt(now())));
        $details = $this->resource->relationLoaded('events');
        $photos = $details ? $this->events->sum('photos_count') : (int) $this->photos_count;
        $videos = $details ? $this->events->sum('videos_count') : (int) $this->videos_count;
        return [...parent::toArray($request),
            'current_subscription' => $current,
            'statistics' => ['events' => $details ? $this->events->count() : (int) $this->events_count,
                'photos' => $photos, 'videos' => $videos, 'total_media' => $photos + $videos],
            'subscriptions' => $this->when($details, fn () => $this->subscriptions),
            'payments' => $this->whenLoaded('payments'),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($event) => [
                'id' => $event->id, 'name' => $event->name, 'event_date' => $event->event_date?->toISOString(),
                'photos_count' => (int) $event->photos_count, 'videos_count' => (int) $event->videos_count,
                'total_media' => $event->photos_count + $event->videos_count,
            ])),
        ];
    }
}
