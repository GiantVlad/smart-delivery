<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    public function list(): JsonResource
    {
        $users = User::orderBy('updated_at', 'desc')->get();

        return UserResource::collection($users);
    }

    public function create(CreateUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }
}
