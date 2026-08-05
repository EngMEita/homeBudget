<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
