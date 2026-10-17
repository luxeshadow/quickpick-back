<?php

namespace App\Actions\SubscriptionPlan;

use App\Models\SubscriptionPlan;

class DeleteSubscriptionPlanAction
{
    public function execute(SubscriptionPlan $subscriptionPlan): void
    {
        $subscriptionPlan->delete();
    }
}
