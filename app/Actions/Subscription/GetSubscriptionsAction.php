<?php

namespace App\Actions\Subscription;

use App\Enums\UserRolesEnum;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetSubscriptionsAction
{
    /** @return Collection<int, Subscription> */
    public function execute(User $user): Collection
    {
        $query = Subscription::query()->with(['subscriptionPlan', 'user']);

        if (! $user->hasRole(UserRolesEnum::ADMIN->value)) {
            $query->whereBelongsTo($user);
        }

        return $query->latest()->get();
    }
}
