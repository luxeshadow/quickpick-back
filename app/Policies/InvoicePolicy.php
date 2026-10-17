<?php

namespace App\Policies;

use App\Enums\UserRolesEnum;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    public function viewAny(User $user): bool { return true; }

    public function view(User $user, Invoice $invoice): Response
    {
        return $invoice->user_id === $user->id || $user->hasRole(UserRolesEnum::ADMIN->value)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à consulter cette facture.');
    }
}
