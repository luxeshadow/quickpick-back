<?php

namespace App\Actions\AccessLink;

use App\Enums\AccessLinkSourceEnum;
use App\Models\AccessLink;
use App\Models\Event;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Validation\ValidationException;

class GenerateAccessLinkAction
{
    public function __construct(private readonly SubscriptionAccessService $subscriptionAccess) {}

    /** @param array<string, mixed> $data */
    public function execute(
        Event $event,
        ?User $creator,
        array $data = [],
        AccessLinkSourceEnum $source = AccessLinkSourceEnum::MANUAL,
    ): AccessLink {
        if (! $event->user) {
            throw ValidationException::withMessages(['access_link' => ['Le propriétaire de l’événement est introuvable.']]);
        }
        $this->subscriptionAccess->requireActive($event->user, 'access_link');

        return $event->accessLinks()->create([
            'created_by' => $creator?->id,
            'token' => bin2hex(random_bytes(32)),
            'source' => $source->value,
            'expires_at' => $data['expires_at'] ?? now()->addDays(30),
            'is_active' => true,
            'max_scans' => $data['max_scans'] ?? null,
        ]);
    }
}
