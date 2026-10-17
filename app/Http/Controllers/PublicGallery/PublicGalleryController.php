<?php

namespace App\Http\Controllers\PublicGallery;

use App\Actions\PublicGallery\BrowseEventMediaAction;
use App\Actions\PublicGallery\DownloadSelectedMediaAction;
use App\Actions\PublicGallery\CreateManualMediaDeliveryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicGallery\DownloadSelectedMediaRequest;
use App\Http\Requests\PublicGallery\StoreManualDeliveryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicGalleryController extends Controller
{
    public function index(string $token, Request $request, BrowseEventMediaAction $action): JsonResponse
    {
        $perPage = min(50, max(10, $request->integer('per_page', 30)));
        $media = $action->execute($token, $perPage);

        return response()->json([
            'status' => 'success',
            'media' => $media->items(),
            'pagination' => [
                'per_page' => $media->perPage(),
                'next_cursor' => $media->nextCursor()?->encode(),
                'previous_cursor' => $media->previousCursor()?->encode(),
                'has_more' => $media->hasMorePages(),
            ],
        ]);
    }

    public function downloads(
        string $token,
        DownloadSelectedMediaRequest $request,
        DownloadSelectedMediaAction $action
    ): JsonResponse {
        $downloads = $action->execute($token, $request->validated('media_ids'));

        return response()->json([
            'status' => 'success',
            'message' => 'Liens de téléchargement générés pour la sélection.',
            'expires_in' => 3600,
            'downloads' => $downloads,
        ]);
    }

    public function delivery(
        string $token,
        StoreManualDeliveryRequest $request,
        CreateManualMediaDeliveryAction $action
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'message' => 'L’envoi des photos sélectionnées a été programmé.',
            'delivery' => $action->execute($token, $request->validated()),
        ], 202);
    }
}

