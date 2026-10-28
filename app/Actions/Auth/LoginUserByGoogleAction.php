<?php

namespace App\Actions\Auth;

use App\Actions\Subscription\ActivateFreeSubscriptionAction;
use App\Models\User;
use App\Enums\UserRolesEnum;
use App\Enums\UserStatusEnum;
use Google\Client;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\BeforeValidException;
use Illuminate\Support\Facades\DB;

class LoginUserByGoogleAction
{
    public function __construct(
        private readonly ActivateFreeSubscriptionAction $activateFreeSubscription,
        private readonly Client $client,
    ) {
    }

    public function execute(string $googleToken): array
    {
        JWT::$leeway = 60;

        try {
            $payload = $this->client->verifyIdToken($googleToken);

            if (!$payload) {
                abort(401, 'Token Google invalide.');
            }

            $email = $payload['email'];
            $fullName = $payload['name'] ?? 'Google User';
            $googleId = $payload['sub'];

            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = DB::transaction(function () use ($fullName, $email, $googleId): User {
                    $newUser = User::create([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => null,
                        'google_id' => $googleId,
                        'password' => null,
                        'status' => UserStatusEnum::ACTIVE->value,
                        'email_verified_at' => now(),
                    ]);

                    $newUser->assignRole(UserRolesEnum::PHOTOGRAPHER->value);
                    $this->activateFreeSubscription->execute($newUser);

                    return $newUser;
                });
            }

            $token = $user
                ->createToken('auth_token')
                ->plainTextToken;

            return [
                'user' => $user->fresh(),
                'token' => $token,
            ];

        } catch (BeforeValidException $e) {
            abort(
                401,
                'Token utilisé trop tôt. Réessaie.'
            );
        }
    }
}
