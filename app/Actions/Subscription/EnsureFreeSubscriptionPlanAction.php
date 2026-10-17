<?php

namespace App\Actions\Subscription;

use App\Models\SubscriptionPlan;

class EnsureFreeSubscriptionPlanAction
{
    public function execute(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->updateOrCreate(
            ['name' => 'Gratuit'],
            [
                'description' => 'Plan gratuit attribué automatiquement à l’inscription.',
                'price' => 0,
                'duration_days' => null,
                'max_events' => 2,
                'max_photos' => 100,
                'accept_video' => false,
                'max_videos' => null,
                'is_active' => true,
            ]
        );
    }
}
