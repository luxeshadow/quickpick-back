<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubscriptionAccessService
{
    public function activeFor(User $user): ?Subscription
    {
        $this->synchronizeExpiredFor($user);

        return $user->subscriptions()
            ->with('subscriptionPlan')
            ->where('status', SubscriptionStatusEnum::ACTIVE->value)
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('starts_at')
            ->first();
    }

    public function hasActive(User $user): bool
    {
        return $this->activeFor($user) !== null;
    }

    public function requireActive(User $user, string $field = 'subscription'): Subscription
    {
        $subscription = $this->activeFor($user);

        if (! $subscription) {
            throw ValidationException::withMessages([
                $field => ['Votre abonnement est expiré ou inactif. Renouvelez-le pour effectuer cette action.'],
            ]);
        }

        return $subscription;
    }

    public function synchronizeExpiredFor(User $user): int
    {
        $expired = $user->subscriptions()
            ->where('status', SubscriptionStatusEnum::ACTIVE->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => SubscriptionStatusEnum::EXPIRED->value]);

        if ($expired > 0 && ! $this->activeQuery($user)->exists()) {
            $user->events()->each(function ($event): void {
                $event->accessLinks()->where('is_active', true)->update(['is_active' => false]);
            });
        }

        return $expired;
    }

    private function activeQuery(User $user)
    {
        return $user->subscriptions()
            ->where('status', SubscriptionStatusEnum::ACTIVE->value)
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}

