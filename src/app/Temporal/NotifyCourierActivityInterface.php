<?php

declare(strict_types=1);

namespace App\Temporal;

use Illuminate\Notifications\Notification;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'NotifyCourierActivity.')]
interface NotifyCourierActivityInterface
{
    /**
     * @param class-string<Notification> $notificationClass
     */
    #[ActivityMethod(name: "NotifyCourier")]
    public function notifyCourier(
        string $courierUuid,
        string $taskUuid,
        string $notificationClass,
        ?array $data = null,
    ): string;
}
