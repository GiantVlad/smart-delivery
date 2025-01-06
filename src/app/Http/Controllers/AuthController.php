<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CourierStatusEnum;
use App\Http\Requests\CreateCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use App\Temporal\UpdateCourierStatusWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Temporal\Client\WorkflowOptions;

class AuthController extends Controller
{
    public function login(): JsonResource
    {
        return response()->json();
    }

    public function logout(): JsonResource
    {
        return response()->json();
    }

    public function register(): JsonResource
    {
        return response()->json();
    }
}
