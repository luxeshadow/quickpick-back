<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;

class GetCurrentSubscriptionAction
{
    public function __construct(private readonly SubscriptionAccessService $subscriptionAccess) {}

    public function execute(User $user): ?Subscription
    {
        return $this->subscriptionAccess->activeFor($user);
    }
}

