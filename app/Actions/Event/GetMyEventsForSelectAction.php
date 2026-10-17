<?php

namespace App\Actions\Event;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetMyEventsForSelectAction
{
    public function execute(User $user): Collection
    {
        return $user->events()
            ->select(['id', 'name', 'status'])
            ->orderBy('name')
            ->get();
    }
}
