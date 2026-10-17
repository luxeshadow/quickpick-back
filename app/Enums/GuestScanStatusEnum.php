<?php

namespace App\Enums;

enum GuestScanStatusEnum: string
{
    case PROCESSING = 'processing';
    case MATCHED = 'matched';
    case NO_MATCH = 'no_match';
    case FAILED = 'failed';
}