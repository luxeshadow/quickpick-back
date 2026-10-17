<?php

namespace App\Actions\SubscriptionPlan;

use App\Models\SubscriptionPlan;

class CreateSubscriptionPlanAction
{
   
    public function execute(array $data): SubscriptionPlan
    {
        return SubscriptionPlan::create($data);
    }
}
