<?php

namespace App\Actions\Payment;

use App\Models\Payment;

class ShowPaymentAction
{
    public function execute(Payment $payment): Payment
    {
        return $payment->load(['subscription.subscriptionPlan', 'invoice']);
    }
}
