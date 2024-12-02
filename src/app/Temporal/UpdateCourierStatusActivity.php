<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Courier;
use Opekunov\Centrifugo\Centrifugo;
use Temporal\Activity;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\IllegalStateException;

class UpdateCourierStatusActivity implements UpdateCourierStatusActivityInterface
{
    public function __construct(private readonly Centrifugo $centrifugo)
    {
    }

    public function updateCourierStatus(string $courierUuid, string $status): string
    {
        $courier = Courier::where('uuid', $courierUuid)->first();
        $courier->status = $status;
        $courier->save();

        $this->centrifugo->publish('courier_status', ['uuid' => $courierUuid, 'status' => $status]);

        return $status;
    }
}
