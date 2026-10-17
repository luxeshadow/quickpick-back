<?php

namespace App\Actions\Payment;

use App\Enums\UserRolesEnum;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetMyPaymentsAction
{
    /** @param array<string, mixed> $filters @return Collection<int, Payment> */
    public function execute(User $user, array $filters): Collection
    {
        $query = Payment::query()->with(['subscription.subscriptionPlan', 'invoice']);

        if (! $user->hasRole(UserRolesEnum::ADMIN->value)) {
            $query->whereBelongsTo($user);
        }

        $query->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date));
        $query->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        return $query->latest()->get();
    }
}
