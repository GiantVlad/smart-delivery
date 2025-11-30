<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateWorkingHoursRequest;
use App\Http\Requests\UpdateWorkingHoursRequest;
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

    public function update(int $id, UpdateWorkingHoursRequest $request): JsonResource
    {
        $wh = WorkingHour::findOrFail($id);

        $wh->from = $request->from;
        $wh->to = $request->to;
        $wh->save();

        return WorkingHoursResource::make($wh);
    }

    public function create(CreateWorkingHoursRequest $request): JsonResource
    {
        $wh = new WorkingHour();

        $wh->courier_id = $request->courier_id;
        $wh->day = $request->day;
        $wh->from = $request->from;
        $wh->to = $request->to;
        $wh->save();

        return WorkingHoursResource::make($wh);
    }
}
