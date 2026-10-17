<?php

namespace App\Http\Controllers\Media;

use App\Actions\Media\GetEventMediaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\IndexMediaRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    public function index(
        IndexMediaRequest $request,
        Event $event,
        GetEventMediaAction $action,
    ): JsonResponse {
        Gate::authorize('view', $event);

        return response()->json([
            'status' => 'success',
            'media' => $action->execute($event, $request->validated('type')),
        ]);
    }
}
