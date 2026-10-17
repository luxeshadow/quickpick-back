<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_creation_generates_a_printable_invoice_and_can_be_cancelled(): void
    {
        $user = $this->user('client@example.com');
        $subscription = $this->subscription($user);
        Sanctum::actingAs($user);

        $paymentId = $this->postJson('/api/payments', [
            'subscription_id' => $subscription->id,
            'provider' => 'stripe',
            'payment_method' => 'card',
            'currency' => 'eur',
            'amount' => 0.01,
        ])->assertCreated()
            ->assertJsonPath('payment.amount', '29.99')
            ->assertJsonPath('payment.currency', 'EUR')
            ->json('payment.id');

        $invoiceId = $this->getJson('/api/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'invoices')
            ->assertJsonPath('invoices.0.client.full_name', 'Client Test')
            ->assertJsonPath('invoices.0.client.email', 'client@example.com')
            ->assertJsonPath('invoices.0.payment.provider', 'stripe')
            ->assertJsonPath('invoices.0.subscription.plan.name', 'Pro')
            ->json('invoices.0.id');

        $this->getJson("/api/invoices/{$invoiceId}")->assertOk();

        $this->deleteJson("/api/payments/{$paymentId}")
            ->assertOk()
            ->assertJsonPath('payment.status', 'cancelled')
            ->assertJsonPath('payment.invoice.status', 'cancelled');

        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'cancelled']);
        $this->assertDatabaseHas('invoices', ['payment_id' => $paymentId, 'status' => 'cancelled']);
    }

    public function test_user_cannot_access_another_users_payment_or_invoice(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other@example.com');
        Sanctum::actingAs($owner);
        $subscription = $this->subscription($owner);

        $response = $this->postJson('/api/payments', [
            'subscription_id' => $subscription->id,
            'provider' => 'stripe',
            'payment_method' => 'card',
        ])->assertCreated();

        $paymentId = $response->json('payment.id');
        $invoiceId = $response->json('payment.invoice.id');
        Sanctum::actingAs($other);

        $this->getJson("/api/payments/{$paymentId}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à consulter ce paiement.');
        $this->getJson("/api/invoices/{$invoiceId}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous n’êtes pas autorisé à consulter cette facture.');
        $this->getJson('/api/payments?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk()
            ->assertJsonCount(0, 'payments');
    }

    private function subscription(User $user): Subscription
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Pro', 'price' => 29.99, 'duration_days' => 30, 'is_active' => true,
        ]);

        return Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);
    }

    private function user(string $email): User
    {
        return User::create([
            'full_name' => 'Client Test',
            'email' => $email,
            'phone' => '+33123456789',
            'password' => 'password',
        ]);
    }
}
