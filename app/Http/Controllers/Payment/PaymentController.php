<?php

namespace App\Http\Controllers\Payment;

use App\Actions\Payment\CancelPaymentAction;
use App\Actions\Payment\CreatePaymentAction;
use App\Actions\Payment\GetMyPaymentsAction;
use App\Actions\Payment\ShowPaymentAction;
use App\Actions\Payment\UpdatePaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\FilterPaymentsRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index(FilterPaymentsRequest $request, GetMyPaymentsAction $action): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);

        return response()->json(['status' => 'success', 'payments' => $action->execute($request->user(), $request->validated())]);
    }

    public function store(StorePaymentRequest $request, CreatePaymentAction $action): JsonResponse
    {
        Gate::authorize('create', Payment::class);
        $subscription = Subscription::findOrFail($request->validated('subscription_id'));
        Gate::authorize('view', $subscription);

        return response()->json([
            'status' => 'success',
            'message' => 'Paiement créé et facture générée avec succès.',
            'payment' => $action->execute($request->user(), $subscription, $request->validated()),
        ], 201);
    }

    public function show(Payment $payment, ShowPaymentAction $action): JsonResponse
    {
        Gate::authorize('view', $payment);
        return response()->json(['status' => 'success', 'payment' => $action->execute($payment)]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment, UpdatePaymentAction $action): JsonResponse
    {
        Gate::authorize('update', $payment);
        return response()->json([
            'status' => 'success',
            'message' => 'Paiement mis à jour avec succès.',
            'payment' => $action->execute($payment, $request->validated()),
        ]);
    }

    public function destroy(Payment $payment, CancelPaymentAction $action): JsonResponse
    {
        Gate::authorize('delete', $payment);
        return response()->json([
            'status' => 'success',
            'message' => 'Paiement annulé avec succès.',
            'payment' => $action->execute($payment),
        ]);
    }
}
