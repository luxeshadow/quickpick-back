<?php

namespace App\Actions\AccessLink;

use App\Models\AccessLink;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use App\Services\Subscription\SubscriptionAccessService;

class ResolvePublicAccessLinkAction
{
    public function __construct(private readonly SubscriptionAccessService $subscriptionAccess) {}

    public function execute(string $token): AccessLink
    {
        $accessLink = AccessLink::query()
            ->where('token', $token)
            ->whereHas('event', fn ($query) => $query->where('status', 'active'))
            ->first();

        if (! $accessLink
            || ! $accessLink->is_active
            || $accessLink->expires_at->isPast()
            || ($accessLink->max_scans !== null && $accessLink->scans_count >= $accessLink->max_scans)
            || ! $this->subscriptionAccess->hasActive($accessLink->event->user)
        ) {
            throw new GoneHttpException('Ce lien d’accès est invalide, expiré ou a atteint sa limite de scans.');
        }

        return $accessLink->load('event:id,name,description,location,event_date,status');
    }
}
