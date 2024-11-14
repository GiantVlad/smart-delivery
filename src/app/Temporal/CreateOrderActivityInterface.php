<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateOrderActivity.')]
interface CreateOrderActivityInterface
{
    #[ActivityMethod(name: "CreateOrder")]
    public function createOrder(
        string $customerUuid,
        string $unitType,
    ): string;
}