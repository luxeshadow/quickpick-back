<?php

namespace App\Policies;

use App\Enums\UserRolesEnum;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): Response
    {
        return $this->canManage($user, $subscription)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à consulter cet abonnement.');
    }

    public function update(User $user, Subscription $subscription): Response
    {
        return $user->hasRole(UserRolesEnum::ADMIN->value)
            ? Response::allow()
            : Response::deny('Vous devez être administrateur pour modifier un abonnement.');
    }

    public function delete(User $user, Subscription $subscription): Response
    {
        return $this->canManage($user, $subscription)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à annuler cet abonnement.');
    }

    private function canManage(User $user, Subscription $subscription): bool
    {
        return $subscription->user_id === $user->id
            || $user->hasRole(UserRolesEnum::ADMIN->value);
    }
}
