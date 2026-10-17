<?php

namespace App\Http\Controllers\Event;

use App\Actions\Event\CreateEventAction;
use App\Actions\Event\DeleteEventAction;
use App\Actions\Event\GetMyEventsAction;
use App\Actions\Event\GetMyEventsByStatusAction;
use App\Actions\Event\SearchMyEventsAction;
use App\Actions\Event\GetMyEventsForSelectAction;
use App\Actions\Event\ShowEventAction;
use App\Actions\Event\UpdateEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\Event\GetMyEventsByStatusRequest;
use App\Http\Requests\Event\SearchMyEventsRequest;
use App\Http\Requests\Event\GetMyEventsRequest;
use App\Enums\EventStatusEnum;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function index(GetMyEventsRequest $request, GetMyEventsAction $action): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);
        $data = $request->validated();

        return response()->json([
            'status' => 'success',
            'events' => $action->execute(
                $request->user(),
                (int) ($data['per_page'] ?? 15),
            ),
        ]);
    }

    public function byStatus(
        GetMyEventsByStatusRequest $request,
        GetMyEventsByStatusAction $action
    ): JsonResponse {
        Gate::authorize('viewAny', Event::class);
        $data = $request->validated();

        return response()->json([
            'status' => 'success',
            'events' => $action->execute(
                $request->user(),
                EventStatusEnum::from($data['status']),
                (int) ($data['per_page'] ?? 15),
            ),
        ]);
    }

    public function search(
        SearchMyEventsRequest $request,
        SearchMyEventsAction $action
    ): JsonResponse {
        Gate::authorize('viewAny', Event::class);
        $data = $request->validated();

        return response()->json([
            'status' => 'success',
            'events' => $action->execute(
                $request->user(),
                $data['search'],
                (int) ($data['per_page'] ?? 15),
            ),
        ]);
    }

    public function options(GetMyEventsForSelectAction $action): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);

        return response()->json([
            'status' => 'success',
            'events' => $action->execute(request()->user()),
        ]);
    }

    public function store(StoreEventRequest $request, CreateEventAction $action): JsonResponse
    {
        Gate::authorize('create', Event::class);

        $event = $action->execute($request->user(), $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Événement créé avec succès.',
            'event' => $event,
        ], 201);
    }

    public function show(Event $event, ShowEventAction $action): JsonResponse
    {
        Gate::authorize('view', $event);

        return response()->json([
            'status' => 'success',
            'event' => $action->execute($event),
        ]);
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        UpdateEventAction $action
    ): JsonResponse {
        Gate::authorize('update', $event);

        return response()->json([
            'status' => 'success',
            'message' => 'Événement mis à jour avec succès.',
            'event' => $action->execute($event, $request->validated()),
        ]);
    }

    public function destroy(Event $event, DeleteEventAction $action): JsonResponse
    {
        Gate::authorize('delete', $event);
        $action->execute($event);

        return response()->json([
            'status' => 'success',
            'message' => 'Événement archivé avec succès.',
        ]);
    }
}
