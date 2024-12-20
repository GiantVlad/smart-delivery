<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'AssignOrderActivity.')]
interface AssignOrderActivityInterface
{
    #[ActivityMethod(name: "AssignOrder")]
    public function assignOrder(
        string $orderUuid,
        string $taskUuid,
    ): OrderDto;
}
