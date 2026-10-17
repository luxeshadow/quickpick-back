<?php

namespace App\Http\Controllers\SubscriptionPlan;

use App\Actions\SubscriptionPlan\CreateSubscriptionPlanAction;
use App\Actions\SubscriptionPlan\DeleteSubscriptionPlanAction;
use App\Actions\SubscriptionPlan\GetSubscriptionPlansAction;
use App\Actions\SubscriptionPlan\ShowSubscriptionPlanAction;
use App\Actions\SubscriptionPlan\UpdateSubscriptionPlanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPlan\StoreSubscriptionPlanRequest;
use App\Http\Requests\SubscriptionPlan\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SubscriptionPlanController extends Controller
{
    public function index(GetSubscriptionPlansAction $action): JsonResponse
    {
        Gate::authorize('viewAny', SubscriptionPlan::class);

        return response()->json([
            'status' => 'success',
            'subscription_plans' => $action->execute(),
        ]);
    }

    public function store(
        StoreSubscriptionPlanRequest $request,
        CreateSubscriptionPlanAction $action
    ): JsonResponse {
        Gate::authorize('create', SubscriptionPlan::class);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan d’abonnement créé avec succès.',
            'subscription_plan' => $action->execute($request->validated()),
        ], 201);
    }

    public function show(
        SubscriptionPlan $subscriptionPlan,
        ShowSubscriptionPlanAction $action
    ): JsonResponse {
        Gate::authorize('view', $subscriptionPlan);

        return response()->json([
            'status' => 'success',
            'subscription_plan' => $action->execute($subscriptionPlan),
        ]);
    }

    public function update(
        UpdateSubscriptionPlanRequest $request,
        SubscriptionPlan $subscriptionPlan,
        UpdateSubscriptionPlanAction $action
    ): JsonResponse {
        Gate::authorize('update', $subscriptionPlan);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan d’abonnement mis à jour avec succès.',
            'subscription_plan' => $action->execute($subscriptionPlan, $request->validated()),
        ]);
    }

    public function destroy(
        SubscriptionPlan $subscriptionPlan,
        DeleteSubscriptionPlanAction $action
    ): JsonResponse {
        Gate::authorize('delete', $subscriptionPlan);
        $action->execute($subscriptionPlan);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan d’abonnement supprimé avec succès.',
        ]);
    }
}
