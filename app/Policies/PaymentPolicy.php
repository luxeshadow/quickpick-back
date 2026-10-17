<?php

namespace App\Policies;

use App\Enums\UserRolesEnum;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function create(User $user): bool { return true; }

    public function view(User $user, Payment $payment): Response
    {
        return $this->ownsOrAdmin($user, $payment)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à consulter ce paiement.');
    }

    public function update(User $user, Payment $payment): Response
    {
        return $this->isAdmin($user)
            ? Response::allow()
            : Response::deny('Vous devez être administrateur pour modifier un paiement.');
    }

    public function delete(User $user, Payment $payment): Response
    {
        return $this->ownsOrAdmin($user, $payment)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à annuler ce paiement.');
    }

    private function ownsOrAdmin(User $user, Payment $payment): bool
    {
        return $payment->user_id === $user->id || $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(UserRolesEnum::ADMIN->value);
    }
}
