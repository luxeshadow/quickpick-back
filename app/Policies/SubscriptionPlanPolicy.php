<?php

namespace App\Policies;

use App\Enums\UserRolesEnum;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubscriptionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        return $this->adminResponse(
            $user,
            'Vous devez être administrateur pour créer un plan d’abonnement.'
        );
    }

    public function update(User $user, SubscriptionPlan $subscriptionPlan): Response
    {
        return $this->adminResponse(
            $user,
            'Vous devez être administrateur pour modifier un plan d’abonnement.'
        );
    }

    public function delete(User $user, SubscriptionPlan $subscriptionPlan): Response
    {
        return $this->adminResponse(
            $user,
            'Vous devez être administrateur pour supprimer un plan d’abonnement.'
        );
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(UserRolesEnum::ADMIN->value);
    }

    private function adminResponse(User $user, string $message): Response
    {
        return $this->isAdmin($user)
            ? Response::allow()
            : Response::deny($message);
    }
}
