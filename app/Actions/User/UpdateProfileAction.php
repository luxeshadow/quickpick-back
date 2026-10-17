<?php

namespace App\Actions\User;

use App\Models\User;

class UpdateProfileAction
{
    public function execute(User $user, array $data): User
    {
        $user->fill($data);
        if ($user->isDirty('email')) { $user->email_verified_at = null; }
        $user->save();
        return $user->refresh();
    }
}
