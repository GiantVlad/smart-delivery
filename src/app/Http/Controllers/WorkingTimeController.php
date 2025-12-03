<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddCourierHolidaysRequest;
use App\Http\Requests\CreateWorkingHoursRequest;
use App\Http\Requests\UpdateWorkingHoursRequest;
use App\Http\Resources\CourierHolidayResource;
use App\Http\Resources\WorkingHoursResource;
use App\Models\CourierHoliday;
use App\Models\WorkingHour;
use Illuminate\Http\JsonResponse;
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

    public function getCourierHolidays(int $courierId): JsonResource
    {
        $data = CourierHoliday::where('courier_id', $courierId)->get();

        return CourierHolidayResource::collection($data);
    }

    public function addCourierHolidays(AddCourierHolidaysRequest $request): JsonResource
    {
        $courierId = $request->get('courier_id');
        $dateFrom = \Carbon\Carbon::parse($request->get('date_from'));
        $dateTo = \Carbon\Carbon::parse($request->get('date_to'));
        $reasonCode = $request->get('reason_code', 0);

        $dates = collect(\Carbon\CarbonPeriod::create($dateFrom, $dateTo))
            ->map(fn($date) => $date->format('Y-m-d'));

        // Get existing holidays for these dates
        $existingHolidays = CourierHoliday::where('courier_id', $courierId)
            ->whereIn('date', $dates)
            ->pluck('date')
            ->toArray();

        $newHolidays = $dates->reject(fn($date) => in_array($date, $existingHolidays))
            ->map(function ($date) use ($courierId, $reasonCode) {
                return [
                    'courier_id' => $courierId,
                    'date' => $date,
                    'reason_code' => $reasonCode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

        if ($newHolidays->isNotEmpty()) {
            CourierHoliday::insert($newHolidays->toArray());
        }

        $holidays = CourierHoliday::where('courier_id', $courierId)->get();

        return CourierHolidayResource::collection($holidays);
    }

    public function removeCourierHolidays(AddCourierHolidaysRequest $request): JsonResponse
    {
        $courierId = $request->get('courier_id');
        $dateFrom = \Carbon\Carbon::parse($request->get('date_from'));
        $dateTo = \Carbon\Carbon::parse($request->get('date_to'));

        CourierHoliday::where('courier_id', $courierId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->delete();

        return response()->json([], 204);
    }
}
