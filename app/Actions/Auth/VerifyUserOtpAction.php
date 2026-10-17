<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class VerifyUserOtpAction
{
    public function execute(User $user, int $otp): array
    {
        $key = "otp:user:{$user->email}";

        $storedOtp = Cache::get($key);

        if (
            !$storedOtp ||
            !hash_equals((string) $storedOtp, (string) $otp)
        ) {
            throw new RuntimeException(
                'Code de vérification invalide ou expiré.'
            );
        }

        // Première vérification du compte
        if (!$user->email_verified_at) {
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => UserStatusEnum::ACTIVE->value,
            ])->save();
        }

        Cache::forget($key);

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return [
            'user' => $user->fresh(),
            'token' => $token,
        ];
    }
}