<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GetUsersAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        // Counts use a single correlated aggregate per type, including all non-archived events.
        $query = User::query()->with(['roles', 'subscriptions' => fn ($q) => $q
            ->with('subscriptionPlan')->where('status', 'active')->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderByDesc('starts_at')->orderByDesc('id')])->withCount('events');
        foreach (['image' => 'photos_count', 'video' => 'videos_count'] as $type => $alias) {
            $query->selectSub(\App\Models\Media::query()->selectRaw('count(*)')
                ->join('events', 'events.id', '=', 'media.event_id')
                ->whereColumn('events.user_id', 'users.id')->whereNull('events.deleted_at')
                ->where('media.media_type', $type)->whereNotNull('media.uploaded_at'), $alias);
        }
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            // Treat wildcard characters as literal characters, not SQL patterns.
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
            $query->where(fn ($q) => $q->whereRaw("LOWER(full_name) LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(email) LIKE ? ESCAPE '!'", [$pattern]));
        }
        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)->withQueryString();
    }
}
