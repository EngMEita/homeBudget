<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmPasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        abort_unless($user && Hash::check($request->string('password'), $user->password), 422);

        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['verified' => true]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['sent' => true]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['verified' => true]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return response()->json(['status' => $status]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        abort_unless($status === Password::PASSWORD_RESET, 422);

        return response()->json(['status' => $status]);
    }

    public function confirmPassword(ConfirmPasswordRequest $request): JsonResponse
    {
        abort_unless(Hash::check($request->string('password'), $request->user()->password), 422);

        DB::table('cache')->updateOrInsert([
            'key' => 'password-confirmed:'.$request->user()->id,
        ], [
            'value' => serialize(now()->timestamp),
            'expiration' => now()->addMinutes(15)->timestamp,
        ]);

        return response()->json(['confirmed' => true, 'expires_in' => 900]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['logged_out' => true]);
    }

    public function sessions(Request $request): JsonResponse
    {
        return response()->json([
            'current_token_id' => $request->user()?->currentAccessToken()?->getKey(),
            'tokens' => $request->user()?->tokens()->get(['id', 'name', 'last_used_at', 'created_at']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $household = $user?->households()->orderBy('households.id')->first();

        if ($household) {
            $household->load([
                'accounts:id,household_id,currency_id,name',
                'accounts.currency:id,code',
            ]);
        }

        return response()->json([
            'user' => $user,
            'household' => $household ? [
                'id' => $household->id,
                'name' => $household->name,
                'base_currency_code' => $household->base_currency_code,
                'accounts' => $household->accounts->map(fn ($account) => [
                    'id' => $account->id,
                    'currency_id' => $account->currency_id,
                    'currency_code' => $account->currency?->code,
                    'name' => $account->name,
                ])->values(),
            ] : null,
        ]);
    }

    public function tokens(Request $request): JsonResponse
    {
        return response()->json([
            'tokens' => $request->user()?->tokens()->get(['id', 'name', 'last_used_at', 'created_at']),
        ]);
    }

    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()?->tokens()->whereKey($tokenId)->firstOrFail();
        $token->delete();

        return response()->json(['revoked' => true]);
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json(['revoked_all' => true]);
    }

    public function rotate(Request $request): JsonResponse
    {
        $currentToken = $request->user()?->currentAccessToken();
        $tokenLabel = $request->string('label')->toString() ?: ($currentToken?->name ?? 'api');
        $token = $request->user()?->createToken($tokenLabel)->plainTextToken;
        $currentToken?->delete();

        return response()->json(['token' => $token]);
    }

    public function revokeCurrentDevice(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['revoked_current' => true]);
    }
}
