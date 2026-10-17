<?php

namespace App\Http\Controllers\GuestScan;

use App\Actions\GuestScan\SearchGuestPhotosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuestScan\StoreGuestScanRequest;
use Illuminate\Http\JsonResponse;

class GuestScanController extends Controller
{
    public function store(string $token, StoreGuestScanRequest $request, SearchGuestPhotosAction $action): JsonResponse
    {
        return response()->json(['status' => 'success', ...$action->execute($token, $request->file('selfie'))]);
    }
}
