<?php

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\UserStatsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stats\UserStatsRequest;
use Illuminate\Http\JsonResponse;

class UserStatsController extends Controller
{
    public function __invoke(UserStatsRequest $request, UserStatsAction $action): JsonResponse
    {
        $data = $request->validated();
        return response()->json(['status' => 'success', ...$action->execute(
            $request->user(), (int) ($data['year'] ?? now()->year),
        )]);
    }
}
