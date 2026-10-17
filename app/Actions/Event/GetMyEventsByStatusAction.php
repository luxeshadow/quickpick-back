<?php

namespace App\Actions\Event;

use App\Enums\EventStatusEnum;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetMyEventsByStatusAction
{
    public function execute(User $user, EventStatusEnum $status, int $perPage = 15): LengthAwarePaginator
    {
        return $user->events()
            ->where('status', $status->value)
            ->latest()
            ->paginate($perPage);
    }
}
