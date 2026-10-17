<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatusEnum;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire les abonnements arrivés à échéance et désactive leurs liens d’accès';

    public function handle(SubscriptionAccessService $access): int
    {
        $users = User::query()
            ->whereHas('subscriptions', fn ($query) => $query
                ->where('status', SubscriptionStatusEnum::ACTIVE->value)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<=', now()))
            ->cursor();

        $subscriptions = 0;
        $usersCount = 0;
        foreach ($users as $user) {
            $subscriptions += $access->synchronizeExpiredFor($user);
            $usersCount++;
        }

        $this->info("{$subscriptions} abonnement(s) expiré(s) pour {$usersCount} utilisateur(s).");

        return self::SUCCESS;
    }
}

