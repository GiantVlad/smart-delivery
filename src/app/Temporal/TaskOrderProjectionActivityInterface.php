<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'TaskOrderProjectionActivity.')]
interface TaskOrderProjectionActivityInterface
{
    #[ActivityMethod(name: 'Attach')]
    public function attach(string $taskUuid, string $orderUuid): OrderDto;

    #[ActivityMethod(name: 'Detach')]
    public function detach(string $taskUuid, string $orderUuid): OrderDto;
}
