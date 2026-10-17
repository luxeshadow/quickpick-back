<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
