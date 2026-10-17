<?php

namespace App\Policies;

use App\Enums\UserRolesEnum;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasRole(UserRolesEnum::ADMIN->value); }
    public function view(User $user, User $target): bool { return $this->viewAny($user); }
    public function update(User $user, User $target): bool { return $user->is($target); }
    public function delete(User $user, User $target): bool { return $this->viewAny($user); }
}
