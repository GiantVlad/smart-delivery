<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateOrderErpActivity.')]
interface CreateOrderErpActivityInterface
{
    #[ActivityMethod(name: "CreateOrderInErp")]
    public function createOrderInErp(
        string $orderUuid,
    ): string;
}
