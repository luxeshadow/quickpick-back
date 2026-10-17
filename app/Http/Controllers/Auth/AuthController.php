<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\LoginUserRequest;
use App\Http\Requests\User\LoginGoogleRequest;
use App\Http\Requests\Otp\StoreOtpRequest;
use App\Http\Requests\Otp\ResendOtpRequest;
use App\Http\Requests\User\ForgotPasswordRequest;
use App\Http\Requests\User\ResetPasswordRequest;

use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\VerifyUserOtpAction;
use App\Actions\Auth\ResendUserOtpAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LoginUserByGoogleAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Actions\Auth\ResetUserPasswordAction;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Register user.
     */
    public function register(
        StoreUserRequest $request,
        RegisterUserAction $action
    ): JsonResponse {
        $user = $action->execute(
            $request->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Compte créé. Un code de vérification a été envoyé.',
            'email' => $user->email,
        ], 201);
    }

    /**
     * Login user with email and password.
     */
    public function login(
        LoginUserRequest $request,
        LoginUserAction $action
    ): JsonResponse {
        $user = $action->execute(
            $request->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Un code de vérification a été envoyé.',
            'email' => $user->email,
        ], 200);
    }

    /**
     * Verify user's OTP.
     */
    public function verifyOtp(
        StoreOtpRequest $request,
        VerifyUserOtpAction $action
    ): JsonResponse {
        $data = $request->validated();

        $user = User::where('email', $data['email'])
            ->firstOrFail();

        $result = $action->execute(
            $user,
            (int) $data['otp']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Code vérifié avec succès.',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 200);
    }

    public function resendOtp(
        ResendOtpRequest $request,
        ResendUserOtpAction $action
    ): JsonResponse {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $action->execute($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Un nouveau code de vérification a été envoyé.',
            'email' => $user->email,
        ]);
    }

    public function forgotPassword(
        ForgotPasswordRequest $request,
        SendPasswordResetLinkAction $action
    ): JsonResponse {
        $user = User::where('email', $request->validated('email'))->firstOrFail();
        $action->execute($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Un lien de réinitialisation a été envoyé par e-mail.',
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        ResetUserPasswordAction $action
    ): JsonResponse {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->firstOrFail();
        $action->execute($user, $data['token'], $data['password']);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }

    /**
     * Login user with Google.
     */
    public function loginByGoogle(
        LoginGoogleRequest $request,
        LoginUserByGoogleAction $action
    ): JsonResponse {
        $data = $request->validated();

        $result = $action->execute(
            $data['token']
        );

        return response()->json([
            'status' => 'success',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 200);
    }

    /**
     * Logout user.
     */
    public function logout(
        Request $request,
        LogoutUserAction $action
    ): JsonResponse {
        $action->execute($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie.',
        ], 200);
    }
}
