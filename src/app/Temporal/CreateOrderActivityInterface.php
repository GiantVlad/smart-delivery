<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateOrderActivity.')]
interface CreateOrderActivityInterface
{
    #[ActivityMethod(name: "CreateOrder")]
    public function createOrder(
        OrderDto $orderDto
    ): string;
}
