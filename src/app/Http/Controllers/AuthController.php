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
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Temporal\Client\WorkflowOptions;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken(
                    'spa-token', ['*'], now()->addWeek()
                )->plainTextToken;
                $response = ['token' => $token];

                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];

                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];

            return response($response, 422);
        }
    }

    public function logout(): JsonResource
    {
        return response()->json();
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
        $response = ['token' => $token];

        return response($response, 200);
    }
}
