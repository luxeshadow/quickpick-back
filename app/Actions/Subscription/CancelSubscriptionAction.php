<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;

class CancelSubscriptionAction
{
    public function execute(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatusEnum::CANCELLED->value,
            'cancelled_at' => $subscription->cancelled_at ?? now(),
        ]);

        return $subscription->refresh();
    }
}
