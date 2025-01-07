<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;


class UserController extends Controller
{
    public function list(): JsonResource
    {
        $users = User::orderBy('updated_at', 'desc')->get();

        return UserResource::collection($users);
    }
}
