<?php

namespace App\Actions\User;

use App\Models\User;

class ShowUserAction
{
    public function execute(User $user): User
    {
        return $user->load([
            'roles', 'subscriptions' => fn ($q) => $q->with('subscriptionPlan')->latest('starts_at')->orderByDesc('id'),
            'payments' => fn ($q) => $q->latest(),
            'events' => fn ($q) => $q->withCount([
                'media as photos_count' => fn ($m) => $m->where('media_type', 'image')->whereNotNull('uploaded_at'),
                'media as videos_count' => fn ($m) => $m->where('media_type', 'video')->whereNotNull('uploaded_at'),
            ])->latest('event_date'),
        ]);
    }
}
