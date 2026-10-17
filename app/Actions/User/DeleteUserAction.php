<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUserAction
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->roles()->detach();
            $user->permissions()->detach();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            // Related business rows are removed by existing database cascade constraints.
            $user->delete();
        });
    }
}
