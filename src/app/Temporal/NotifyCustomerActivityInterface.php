<?php

declare(strict_types=1);

namespace App\Temporal;

use Illuminate\Notifications\Notification;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'NotifyCustomerActivity.')]
interface NotifyCustomerActivityInterface
{
    /**
     * @param class-string<Notification> $notificationClass
     */
    #[ActivityMethod(name: "NotifyCustomer")]
    public function notifyCustomer(
        string $customerUuid,
        string $orderUuid,
        string $notificationClass,
    ): string;
}
