<?php

namespace App\Policies;

use App\Enums\UserRolesEnum;
use App\Models\AccessLink;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccessLinkPolicy
{
    public function view(User $user, AccessLink $accessLink): Response
    {
        return $this->ownsEventOrAdmin($user, $accessLink)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à consulter ce lien d’accès.');
    }

    public function update(User $user, AccessLink $accessLink): Response
    {
        return $this->ownsEventOrAdmin($user, $accessLink)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à modifier ce lien d’accès.');
    }

    public function delete(User $user, AccessLink $accessLink): Response
    {
        return $this->ownsEventOrAdmin($user, $accessLink)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à supprimer ce lien d’accès.');
    }

    private function ownsEventOrAdmin(User $user, AccessLink $accessLink): bool
    {
        return $accessLink->event()->withTrashed()->where('user_id', $user->id)->exists()
            || $user->hasRole(UserRolesEnum::ADMIN->value);
    }
}
