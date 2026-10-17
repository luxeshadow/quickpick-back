<?php

namespace App\Actions\Payment;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPaymentAction
{
    public function execute(Payment $payment): Payment
    {
        if (in_array($payment->status, [PaymentStatusEnum::PAID, PaymentStatusEnum::REFUNDED], true)) {
            throw ValidationException::withMessages([
                'payment' => ['Un paiement encaissé ou remboursé ne peut pas être annulé.'],
            ]);
        }

        return DB::transaction(function () use ($payment): Payment {
            $payment->update(['status' => PaymentStatusEnum::CANCELLED->value]);
            $payment->invoice?->update(['status' => InvoiceStatusEnum::CANCELLED->value]);

            return $payment->refresh()->load('invoice');
        });
    }
}
