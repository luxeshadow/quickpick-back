<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetUserPasswordAction
{
    public function execute(User $user, string $token, string $password): void
    {
        $key = "password-reset:user:{$user->email}";
        $storedToken = Cache::get($key);

        if (! $storedToken || ! hash_equals((string) $storedToken, hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'token' => ['Ce lien de réinitialisation est invalide ou expiré.'],
            ]);
        }

        if (Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Le nouveau mot de passe doit être différent de l’ancien.'],
            ]);
        }

        $user->update(['password' => $password]);
        $user->tokens()->delete();
        Cache::forget($key);
    }
}
