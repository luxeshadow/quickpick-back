<?php

namespace App\Actions\Stats;

use App\Models\Media;
use App\Models\User;
use Carbon\CarbonImmutable;

class UserStatsAction
{
    public function execute(User $user, int $year): array
    {
        $events = $user->events()->withCount([
            'media as photos_count' => fn ($q) => $q->where('media_type', 'image')->whereNotNull('uploaded_at'),
            'media as videos_count' => fn ($q) => $q->where('media_type', 'video')->whereNotNull('uploaded_at'),
        ])->orderByRaw('COALESCE(event_date, created_at) DESC')->orderByDesc('id')->limit(3)->get();

        $media = Media::query()->whereHas('event', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('uploaded_at')->selectRaw(
                'SUM(CASE WHEN media_type = ? THEN 1 ELSE 0 END) as photos, SUM(CASE WHEN media_type = ? THEN 1 ELSE 0 END) as videos',
                ['image', 'video'],
            )->first();
        $photos = (int) $media->photos;
        $videos = (int) $media->videos;

        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $monthly = array_fill(0, 12, 0);
        $dates = $user->events()
            ->whereRaw('COALESCE(event_date, created_at) >= ?', [$start])
            ->whereRaw('COALESCE(event_date, created_at) < ?', [$start->addYear()])
            ->get(['event_date', 'created_at']);
        foreach ($dates as $event) {
            $date = $event->event_date ?? $event->created_at;
            $monthly[$date->month - 1]++;
        }

        return [
            'user_id' => $user->id,
            'statistics' => ['events' => $user->events()->count(), 'photos' => $photos,
                'videos' => $videos, 'total_media' => $photos + $videos],
            'events' => $events->map(fn ($event) => [
                'id' => $event->id, 'name' => $event->name,
                'event_date' => $event->event_date?->toISOString(),
                'photos_count' => (int) $event->photos_count,
                'videos_count' => (int) $event->videos_count,
                'total_media' => $event->photos_count + $event->videos_count,
            ])->values()->all(),
            'events_by_month' => [
                'year' => $year, 'total' => array_sum($monthly),
                'date_basis' => 'event_date_or_created_at',
                'labels' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
                'datasets' => [['label' => 'Événements', 'data' => $monthly]],
            ],
        ];
    }
}
