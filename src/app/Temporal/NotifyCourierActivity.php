<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Courier;
use Illuminate\Notifications\Notification;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class NotifyCourierActivity implements NotifyCourierActivityInterface
{
    /**
     * @param class-string<Notification> $notificationClass
     */
    public function notifyCourier(
        string $courierUuid,
        string $taskUuid,
        string $notificationClass,
        ?array $data = null,
    ): string {
        $courier = Courier::where('uuid', $courierUuid)->firstOrFail();
        $courier->notify(new $notificationClass($taskUuid));

        return $taskUuid;
    }
}
