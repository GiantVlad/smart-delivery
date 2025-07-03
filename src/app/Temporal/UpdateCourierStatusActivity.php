<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Facades\CentrifugoFacade;
use App\Models\Courier;
use Illuminate\Support\Facades\Log;
use Opekunov\Centrifugo\Exceptions\CentrifugoException;

class UpdateCourierStatusActivity implements UpdateCourierStatusActivityInterface
{
    public function updateCourierStatus(string $courierUuid, string $status): string
    {
        $courier = Courier::where('uuid', $courierUuid)->first();
        $courier->status = $status;
        $courier->save();
        try {
            CentrifugoFacade::publish('courier_status', ['uuid' => $courierUuid, 'status' => $status]);
        } catch (CentrifugoException $e) {
            Log::error($e);
        }

        return $status;
    }
}
