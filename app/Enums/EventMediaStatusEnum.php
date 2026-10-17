<?php

namespace App\Enums;

enum EventMediaStatusEnum: string
{
    case EMPTY = 'empty';
    case UPLOADING = 'uploading';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case PARTIAL_FAILURE = 'partial_failure';
    case FAILED = 'failed';
}
