<?php

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\Payment;

class CreateInvoiceAction
{
    public function execute(Payment $payment): Invoice
    {
        $isPaid = $payment->status === PaymentStatusEnum::PAID;

        return Invoice::create([
            'user_id' => $payment->user_id,
            'subscription_id' => $payment->subscription_id,
            'payment_id' => $payment->id,
            'invoice_number' => sprintf('INV-%s-%08d', now()->format('Ymd'), $payment->id),
            'subtotal' => $payment->amount,
            'tax' => 0,
            'total' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $isPaid ? InvoiceStatusEnum::PAID->value : InvoiceStatusEnum::ISSUED->value,
            'issued_at' => now(),
            'due_at' => $isPaid ? null : now()->addDays(7),
            'paid_at' => $payment->paid_at,
        ]);
    }
}
