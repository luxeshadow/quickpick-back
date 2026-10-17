<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case ISSUED = 'issued';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
