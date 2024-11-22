<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierController extends Controller
{
    public function get(string $statuses = ''): JsonResource
    {
        $couriers = Courier::query();

        if ($statuses) {
            $couriers = $couriers->whereIn('status', explode(',', $statuses));
        }

        $couriers = $couriers->get();

        return CourierResource::collection($couriers);
    }

    public function getCourier(string $uuid): JsonResource
    {
        $courier = Courier::whereUuid($uuid)->first();

        return CourierResource::make($courier);
    }

    public function updateCourier(UpdateCourierRequest $request): JsonResource
    {
        $courier = Courier::whereUuid($request->get('uuid'))->first();

        return CourierResource::make($courier);
    }
}
