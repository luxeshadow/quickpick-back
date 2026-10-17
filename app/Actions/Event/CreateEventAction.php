<?php

namespace App\Actions\Event;

use App\Models\Event;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Validation\ValidationException;

class CreateEventAction
{
    public function __construct(private readonly SubscriptionAccessService $subscriptionAccess) {}

    public function execute(User $user, array $data): Event
    {
        $subscription = $this->subscriptionAccess->requireActive($user, 'event');

        $maxEvents = $subscription?->subscriptionPlan?->max_events;

        if ($maxEvents !== null && $user->events()->count() >= $maxEvents) {
            throw ValidationException::withMessages([
                'event' => [
                    "Votre plan d’abonnement autorise au maximum {$maxEvents} événements non archivés.",
                ],
            ]);
        }

        return $user->events()->create($data);
    }
}
