<?php

namespace App\Actions\SubscriptionPlan;

use App\Models\SubscriptionPlan;

class ShowSubscriptionPlanAction
{
    public function execute(SubscriptionPlan $subscriptionPlan): SubscriptionPlan
    {
        return $subscriptionPlan;
    }
}
