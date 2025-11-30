<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\WorkingHoursResource;
use App\Models\WorkingHour;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingTimeController extends Controller
{
    public function getCourierWorkingHours(int $courierId): JsonResource
    {
        $data = WorkingHour::where('courier_id', $courierId)->get();

        return WorkingHoursResource::collection($data);
    }
}
