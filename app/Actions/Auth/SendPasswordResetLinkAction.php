<?php

namespace App\Actions\Auth;

use App\Mail\PasswordResetLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetLinkAction
{
    public function execute(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $key = "password-reset:user:{$user->email}";

        Cache::forget($key);
        Cache::put($key, hash('sha256', $token), now()->addMinutes(30));

        $url = rtrim((string) config('app.frontend_url'), '/')
            .'/auth/new-password?'.http_build_query([
                'email' => $user->email,
                'token' => $token,
            ]);

        Mail::to($user->email)->send(new PasswordResetLinkMail($user, $url));
    }
}
