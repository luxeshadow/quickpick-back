<?php

namespace App\Actions\Auth;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ResendUserOtpAction
{
    public function execute(User $user): void
    {
        $key = "otp:user:{$user->email}";

        
        Cache::forget($key);

        $otp = random_int(100000, 999999);

        Cache::put($key, $otp, now()->addMinutes(10));

        Mail::to($user->email)->send(new OtpMail($user, $otp));
    }
}
