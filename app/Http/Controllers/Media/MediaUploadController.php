<?php

namespace App\Http\Controllers\Media;

use App\Actions\Media\DirectMediaUploadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\DirectMediaUploadRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MediaUploadController extends Controller
{
    public function __invoke(
        DirectMediaUploadRequest $request,
        Event $event,
        DirectMediaUploadAction $action,
    ): JsonResponse {
        Gate::authorize('view', $event);
        $result = $action->execute($request->user(), $event, $request->file('files'));

        return response()->json([
            'status' => $result['failed_files'] === [] ? 'success' : 'partial_failure',
            'message' => $result['failed_files'] === []
                ? 'Tous les médias ont été envoyés.'
                : 'Certains médias n’ont pas pu être envoyés.',
            'upload_batch' => $result['batch'],
            'uploaded_files' => $result['uploaded_files'],
            'failed_files' => $result['failed_files'],
            'event' => $result['event'],
            'media' => $result['event']->media,
        ], $result['failed_files'] === [] ? 201 : 207);
    }
}
