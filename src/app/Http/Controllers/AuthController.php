<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            return response()->json([
                'data' => [
                    'email' => Auth::user()?->email,
                    'name' => Auth::user()?->name,
                ],
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 422);
    }

    public function logout(): JsonResponse
    {
        try {
            // Revoke the current user's token
            if (auth()->check()) {
                auth()->user()->currentAccessToken()?->delete();
            }

            // Clear session data
            auth()->logout();
            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error: '.$e->getMessage());

            return response()->json([
                'message' => 'Error during logout',
            ], 500);
        }
    }
}
