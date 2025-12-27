<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CourierStatusEnum;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\CreateCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use App\Models\User;
use App\Temporal\UpdateCourierStatusWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Temporal\Client\WorkflowOptions;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $response = ['data' => ['email' => $credentials['email'], 'name' =>  Auth::user()?->name]];

            return response($response, 200);

        } else {
            $response = ["message" => "Password mismatch"];

            return response($response, 422);
        }
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
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error during logout'
            ], 500);
        }
    }

    public function register(AuthRegisterRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            return response(['message' => 'User already exist'], 422);
        }
        $user = User::create($request->toArray());
        $token = $user->createToken(
            'spa-token', ['*'], now()->addWeek()
        )->plainTextToken;
        $response = ['data' => ['token' => $token, 'name' => $user->name, 'email' => $user->email]];

        return response($response, 200);
    }
}
