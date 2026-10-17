<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSubscriptionAction
{
    public function execute(User $user, SubscriptionPlan $plan): Subscription
    {
        return DB::transaction(function () use ($user, $plan): Subscription {
            $hasCurrentSubscription = $user->subscriptions()
                ->where('status', SubscriptionStatusEnum::ACTIVE->value)
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->lockForUpdate()
                ->exists();

            if ($hasCurrentSubscription) {
                throw ValidationException::withMessages([
                    'subscription_plan_id' => [
                        'Vous avez déjà un abonnement en cours. Vous devez l’annuler ou attendre son expiration avant d’en souscrire un autre.',
                    ],
                ]);
            }

            $startsAt = now();

            return $user->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatusEnum::ACTIVE->value,
                'starts_at' => $startsAt,
                'ends_at' => $plan->duration_days === null
                    ? null
                    : $startsAt->copy()->addDays($plan->duration_days),
            ])->load('subscriptionPlan');
        });
    }
}
