<?php

namespace App\Actions\Event;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchMyEventsAction
{
    public function execute(User $user, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $user->events()
            ->whereLike('name', '%'.$search.'%')
            ->latest()
            ->paginate($perPage);
    }
}
