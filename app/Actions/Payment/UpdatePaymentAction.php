<?php

namespace App\Actions\Payment;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class UpdatePaymentAction
{
  
    public function execute(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data): Payment {
            if (($data['status'] ?? null) === PaymentStatusEnum::PAID->value) {
                $data['paid_at'] ??= now();
            }

            $payment->update($data);

            if ($payment->invoice) {
                if ($payment->status === PaymentStatusEnum::PAID) {
                    $payment->invoice->update([
                        'status' => InvoiceStatusEnum::PAID->value,
                        'paid_at' => $payment->paid_at,
                        'due_at' => null,
                    ]);
                } elseif ($payment->status === PaymentStatusEnum::CANCELLED) {
                    $payment->invoice->update(['status' => InvoiceStatusEnum::CANCELLED->value]);
                }
            }

            return $payment->refresh()->load(['subscription.subscriptionPlan', 'invoice']);
        });
    }
}
