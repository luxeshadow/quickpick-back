<?php

namespace App\Actions\Invoice;

use App\Enums\UserRolesEnum;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;

class GetMyInvoicesAction
{
    public function __construct(private readonly FormatInvoiceAction $formatter) {}

    /** @return Collection<int, array<string, mixed>> */
    public function execute(User $user): Collection
    {
        $query = Invoice::query()->with(['user', 'payment', 'subscription.subscriptionPlan']);

        if (! $user->hasRole(UserRolesEnum::ADMIN->value)) {
            $query->whereBelongsTo($user);
        }

        return $query->latest('issued_at')->get()
            ->map(fn (Invoice $invoice) => $this->formatter->execute($invoice));
    }
}
