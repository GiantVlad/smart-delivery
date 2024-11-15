<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Courier;
use App\Notifications\TaskCreatedNotification;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class NotifyTaskCreatedActivity implements NotifyTaskCreatedActivityInterface
{
    public function notifyTaskCreated(string $courierUuid, string $taskUuid): string
    {
        $courier = Courier::where('uuid', $courierUuid)->firstOrFail();
        $courier->notify(new TaskCreatedNotification($taskUuid));

        return $taskUuid;
    }
}
