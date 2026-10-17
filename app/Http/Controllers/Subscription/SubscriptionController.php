<?php

namespace App\Http\Controllers\Subscription;

use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\CreateSubscriptionAction;
use App\Actions\Subscription\GetSubscriptionsAction;
use App\Actions\Subscription\ShowSubscriptionAction;
use App\Actions\Subscription\UpdateSubscriptionAction;
use App\Actions\Subscription\GetCurrentSubscriptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    public function current(GetCurrentSubscriptionAction $action): JsonResponse
    {
        $subscription = $action->execute(request()->user());

        return response()->json([
            'status' => 'success',
            'has_active_subscription' => $subscription !== null,
            'subscription' => $subscription,
            'message' => $subscription
                ? 'Votre abonnement est actif.'
                : 'Vous n’avez aucun abonnement actif.',
        ]);
    }

    public function index(GetSubscriptionsAction $action): JsonResponse
    {
        Gate::authorize('viewAny', Subscription::class);

        return response()->json([
            'status' => 'success',
            'subscriptions' => $action->execute(request()->user()),
        ]);
    }

    public function store(
        StoreSubscriptionRequest $request,
        CreateSubscriptionAction $action
    ): JsonResponse {
        Gate::authorize('create', Subscription::class);
        $plan = SubscriptionPlan::findOrFail($request->validated('subscription_plan_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement créé avec succès.',
            'subscription' => $action->execute($request->user(), $plan),
        ], 201);
    }

    public function show(
        Subscription $subscription,
        ShowSubscriptionAction $action
    ): JsonResponse {
        Gate::authorize('view', $subscription);

        return response()->json([
            'status' => 'success',
            'subscription' => $action->execute($subscription),
        ]);
    }

    public function update(
        UpdateSubscriptionRequest $request,
        Subscription $subscription,
        UpdateSubscriptionAction $action
    ): JsonResponse {
        Gate::authorize('update', $subscription);

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement mis à jour avec succès.',
            'subscription' => $action->execute($subscription, $request->validated()),
        ]);
    }

    public function destroy(
        Subscription $subscription,
        CancelSubscriptionAction $action
    ): JsonResponse {
        Gate::authorize('delete', $subscription);

        return response()->json([
            'status' => 'success',
            'message' => 'Abonnement annulé avec succès.',
            'subscription' => $action->execute($subscription),
        ]);
    }
}
