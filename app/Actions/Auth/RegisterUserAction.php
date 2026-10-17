<?php

namespace App\Actions\Auth;

use App\Actions\Subscription\ActivateFreeSubscriptionAction;
use App\Models\User;
use App\Enums\UserRolesEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class RegisterUserAction
{
    public function __construct(
        private readonly ActivateFreeSubscriptionAction $activateFreeSubscription
    ) {
    }

    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'full_name'         => $data['full_name'],
                'email'             => $data['email'],
                'phone'             => $data['phone'] ?? null,
                'password'          => $data['password'],
                'status'            => UserStatusEnum::PENDING->value,
                'email_verified_at' => null,
            ]);

            $user->assignRole(UserRolesEnum::PHOTOGRAPHER->value);
            $this->activateFreeSubscription->execute($user);

            $otp = random_int(100000, 999999);

            Cache::put(
                "otp:user:{$user->email}",
                $otp,
                now()->addMinutes(10)
            );

            DB::afterCommit(function () use ($user, $otp) {
                Mail::to($user->email)->send(
                    new OtpMail($user, $otp)
                );
            });

            return $user;
        });
    }
}
