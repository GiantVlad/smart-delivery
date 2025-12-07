<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enums\CourierStatusEnum;
use App\Models\Courier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CourierManager
{
    public function getFreeCouriersForDay(Carbon $date)
    {
        $dayOfWeek = strtolower($date->dayName);

        return Courier::where('status', CourierStatusEnum::RD->value)
            ->whereDoesntHave('holidays', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })
            ->whereHas('workingHours', function ($query) use ($date, $dayOfWeek) {
                $query->where('day', $dayOfWeek);
            })
            ->get();
    }
}
