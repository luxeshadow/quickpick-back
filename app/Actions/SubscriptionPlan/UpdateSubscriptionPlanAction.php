<?php

namespace App\Actions\SubscriptionPlan;

use App\Models\SubscriptionPlan;

class UpdateSubscriptionPlanAction
{
    /** @param array<string, mixed> $data */
    public function execute(SubscriptionPlan $subscriptionPlan, array $data): SubscriptionPlan
    {
        $subscriptionPlan->update($data);

        return $subscriptionPlan->refresh();
    }
}
