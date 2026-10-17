<?php

namespace App\Actions\Event;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetMyEventsAction
{
    public function execute(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->events()->latest()->paginate($perPage);
    }
}
