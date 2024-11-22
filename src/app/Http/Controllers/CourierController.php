<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CourierStatusEnum;
use App\Http\Requests\CreateCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CourierController extends Controller
{
    public function get(string $statuses = ''): JsonResource
    {
        $couriers = Courier::query();

        if ($statuses) {
            $couriers = $couriers->whereIn('status', explode(',', $statuses));
        }

        $couriers = $couriers->orderBy('updated_at', 'desc')->get();

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

        $courier->name = $request->get('name');
        $courier->status = $request->get('status');
        $courier->save();

        return CourierResource::make($courier);
    }

    public function createCourier(CreateCourierRequest $request): JsonResource
    {
        $courier = new Courier();
        $courier->uuid = Str::uuid();
        $courier->name = $request->get('name');
        $courier->status = CourierStatusEnum::NW->value;
        $courier->save();

        return CourierResource::make($courier);
    }
}
