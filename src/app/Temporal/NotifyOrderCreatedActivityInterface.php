<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateOrderActivity.')]
interface NotifyOrderCreatedActivityInterface
{
    #[ActivityMethod(name: "NotifyOrderCreated")]
    public function notifyOrderCreated(
        string $customerUuid,
        string $orderUuid,
    ): string;
}
