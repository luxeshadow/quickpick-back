<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class FormatInvoiceAction
{
    /** @return array<string, mixed> */
    public function execute(Invoice $invoice): array
    {
        $invoice->loadMissing(['user', 'payment', 'subscription.subscriptionPlan']);

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'currency' => $invoice->currency,
            'issued_at' => $invoice->issued_at,
            'due_at' => $invoice->due_at,
            'paid_at' => $invoice->paid_at,
            'client' => [
                'id' => $invoice->user->id,
                'full_name' => $invoice->user->full_name,
                'email' => $invoice->user->email,
                'phone' => $invoice->user->phone,
            ],
            'payment' => [
                'id' => $invoice->payment->id,
                'provider' => $invoice->payment->provider,
                'payment_method' => $invoice->payment->payment_method,
                'amount' => $invoice->payment->amount,
                'currency' => $invoice->payment->currency,
                'status' => $invoice->payment->status,
                'external_reference' => $invoice->payment->external_reference,
                'paid_at' => $invoice->payment->paid_at,
            ],
            'subscription' => [
                'id' => $invoice->subscription->id,
                'status' => $invoice->subscription->status,
                'starts_at' => $invoice->subscription->starts_at,
                'ends_at' => $invoice->subscription->ends_at,
                'plan' => [
                    'id' => $invoice->subscription->subscriptionPlan->id,
                    'name' => $invoice->subscription->subscriptionPlan->name,
                    'description' => $invoice->subscription->subscriptionPlan->description,
                    'max_events' => $invoice->subscription->subscriptionPlan->max_events,
                    'max_photos' => $invoice->subscription->subscriptionPlan->max_photos,
                ],
            ],
        ];
    }
}
