<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class UpdateSubscriptionAction
{
    /** @param array<string, mixed> $data */
    public function execute(Subscription $subscription, array $data): Subscription
    {
        if (isset($data['subscription_plan_id'])) {
            $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
            $startsAt = now();
            $data['starts_at'] = $startsAt;
            $data['ends_at'] = $plan->duration_days === null
                ? null
                : $startsAt->copy()->addDays($plan->duration_days);
        }

        if (($data['status'] ?? null) === SubscriptionStatusEnum::CANCELLED->value) {
            $data['cancelled_at'] = now();
        } elseif (($data['status'] ?? null) === SubscriptionStatusEnum::ACTIVE->value) {
            $data['cancelled_at'] = null;
        }

        $subscription->update($data);

        return $subscription->refresh()->load('subscriptionPlan');
    }
}
