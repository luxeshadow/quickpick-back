<?php

namespace App\Actions\Payment;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePaymentAction
{
    public function __construct(private readonly CreateInvoiceAction $createInvoice) {}

    public function execute(User $user, Subscription $subscription, array $data): Payment
    {
        return DB::transaction(function () use ($user, $subscription, $data): Payment {
            $subscription->loadMissing('subscriptionPlan');

            $alreadyExists = $subscription->payments()
                ->whereIn('status', [PaymentStatusEnum::PENDING->value, PaymentStatusEnum::PAID->value])
                ->lockForUpdate()
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'subscription_id' => ['Un paiement en attente ou payé existe déjà pour cet abonnement.'],
                ]);
            }

            $payment = Payment::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'provider' => $data['provider'],
                'payment_method' => $data['payment_method'],
                'amount' => $subscription->subscriptionPlan->price,
                'currency' => $data['currency'] ?? 'EUR',
                'status' => PaymentStatusEnum::PENDING->value,
                'external_reference' => $data['external_reference'] ?? null,
            ]);

            $this->createInvoice->execute($payment);

            return $payment->load(['subscription.subscriptionPlan', 'invoice']);
        });
    }
}
