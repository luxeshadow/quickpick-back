<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\User;

class ActivateFreeSubscriptionAction
{
    public function __construct(private readonly EnsureFreeSubscriptionPlanAction $ensureFreePlan) {
    }

    public function execute(User $user): Subscription
    {
        $currentSubscription = $user->subscriptions()
            ->where('status', SubscriptionStatusEnum::ACTIVE->value)
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->first();

        if ($currentSubscription) {
            return $currentSubscription;
        }

        $freePlan = $this->ensureFreePlan->execute();

        return $user->subscriptions()->create([
            'subscription_plan_id' => $freePlan->id,
            'status' => SubscriptionStatusEnum::ACTIVE->value,
            'starts_at' => now(),
            'ends_at' => null,
        ]);
    }
}
