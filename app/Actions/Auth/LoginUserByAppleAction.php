<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Enums\UserStatusEnum;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class LoginUserAction
{
    public function execute(array $data): User
    {
        $user = User::where('email', $data['email'])->first();

        if (
            !$user ||
            !Hash::check($data['password'], $user->password)
        ) {
            throw new RuntimeException(
                'Email ou mot de passe incorrect.'
            );
        }

        if ($user->status !== UserStatusEnum::ACTIVE) {
            throw new RuntimeException(
                'Votre compte n’est pas actif.'
            );
        }

        $otp = random_int(100000, 999999);

        Cache::put(
            "otp:user:{$user->email}",
            $otp,
            now()->addMinutes(10)
        );

        Mail::to($user->email)->send(
            new OtpMail($user, $otp)
        );

        return $user;
    }
}
