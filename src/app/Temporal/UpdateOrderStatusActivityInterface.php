<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'UpdateOrderStatusActivity.')]
interface UpdateOrderStatusActivityInterface
{
    #[ActivityMethod(name: "UpdateOrderStatus")]
    public function updateOrderStatus(
        string $orderUuid,
        string $status,
    ): string;
}
