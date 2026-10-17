<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;

class ShowSubscriptionAction
{
    public function execute(Subscription $subscription): Subscription
    {
        return $subscription->load(['subscriptionPlan', 'user']);
    }
}
