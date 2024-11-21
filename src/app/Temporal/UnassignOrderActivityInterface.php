<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'UnassignOrderActivity.')]
interface UnassignOrderActivityInterface
{
    #[ActivityMethod(name: "UnassignOrder")]
    public function unassignOrder(
        string $orderUuid,
    ): OrderDto;
}
