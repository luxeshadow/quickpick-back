<?php

namespace App\Http\Controllers\AccessLink;

use App\Actions\AccessLink\DeleteAccessLinkAction;
use App\Actions\AccessLink\GenerateAccessLinkAction;
use App\Actions\AccessLink\GetEventAccessLinksAction;
use App\Actions\AccessLink\ResolvePublicAccessLinkAction;
use App\Actions\AccessLink\ShowAccessLinkAction;
use App\Actions\AccessLink\UpdateAccessLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccessLink\StoreAccessLinkRequest;
use App\Http\Requests\AccessLink\UpdateAccessLinkRequest;
use App\Models\AccessLink;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AccessLinkController extends Controller
{
    public function index(Event $event, GetEventAccessLinksAction $action): JsonResponse
    {
        Gate::authorize('view', $event);
        return response()->json(['status' => 'success', 'access_links' => $action->execute($event)]);
    }

    public function store(
        StoreAccessLinkRequest $request,
        Event $event,
        GenerateAccessLinkAction $action
    ): JsonResponse {
        Gate::authorize('view', $event);

        return response()->json([
            'status' => 'success',
            'message' => 'Lien d’accès généré avec succès.',
            'access_link' => $action->execute($event, $request->user(), $request->validated()),
        ], 201);
    }

    public function show(AccessLink $accessLink, ShowAccessLinkAction $action): JsonResponse
    {
        Gate::authorize('view', $accessLink);
        return response()->json(['status' => 'success', 'access_link' => $action->execute($accessLink)]);
    }

    public function update(
        UpdateAccessLinkRequest $request,
        AccessLink $accessLink,
        UpdateAccessLinkAction $action
    ): JsonResponse {
        Gate::authorize('update', $accessLink);
        return response()->json([
            'status' => 'success',
            'message' => 'Lien d’accès mis à jour avec succès.',
            'access_link' => $action->execute($accessLink, $request->validated()),
        ]);
    }

    public function destroy(AccessLink $accessLink, DeleteAccessLinkAction $action): JsonResponse
    {
        Gate::authorize('delete', $accessLink);
        $action->execute($accessLink);

        return response()->json([
            'status' => 'success',
            'message' => 'Lien d’accès supprimé définitivement.',
        ]);
    }

    public function resolve(string $token, ResolvePublicAccessLinkAction $action): JsonResponse
    {
        $accessLink = $action->execute($token);

        return response()->json([
            'status' => 'success',
            'access_link' => [
                'expires_at' => $accessLink->expires_at,
                'remaining_scans' => $accessLink->max_scans === null
                    ? null
                    : max(0, $accessLink->max_scans - $accessLink->scans_count),
                'event' => $accessLink->event,
            ],
        ]);
    }
}
