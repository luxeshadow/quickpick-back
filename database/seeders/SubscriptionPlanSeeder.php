<?php

namespace Database\Seeders;

use App\Actions\Subscription\EnsureFreeSubscriptionPlanAction;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(EnsureFreeSubscriptionPlanAction $action): void
    {
        $action->execute();
    }
}
