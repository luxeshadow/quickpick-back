<?php

namespace App\Http\Controllers\User;

use App\Actions\User\DeleteUserAction;
use App\Actions\User\GetUsersAction;
use App\Actions\User\ShowUserAction;
use App\Actions\User\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\AdminUserResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        return response()->json(['status' => 'success', 'message' => 'Profil mis à jour avec succès.',
            'user' => new UserResource($action->execute($request->user(), $request->validated()))]);
    }

    public function index(ListUsersRequest $request, GetUsersAction $action): JsonResponse
    {
        $users = $action->execute($request->validated());
        return response()->json(['status' => 'success', 'users' => AdminUserResource::collection($users),
            'pagination' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(), 'total' => $users->total()]]);
    }

    public function show(User $user, ShowUserAction $action): JsonResponse
    {
        Gate::authorize('view', $user);
        return response()->json(['status' => 'success', 'user' => new AdminUserResource($action->execute($user))]);
    }

    public function destroy(User $user, DeleteUserAction $action): JsonResponse
    {
        Gate::authorize('delete', $user);
        $action->execute($user);
        return response()->json(['status' => 'success', 'message' => 'Utilisateur supprimé avec succès.']);
    }
}
