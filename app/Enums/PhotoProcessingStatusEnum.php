<?php

namespace App\Enums;

enum PhotoProcessingStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
