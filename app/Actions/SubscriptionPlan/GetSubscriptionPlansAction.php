<?php

namespace App\Actions\SubscriptionPlan;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Collection;

class GetSubscriptionPlansAction
{
    /** @return Collection<int, SubscriptionPlan> */
    public function execute(): Collection
    {
        return SubscriptionPlan::query()->whereNull('deleted_at')->latest()->get();
    }
}
