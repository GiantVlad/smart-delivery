<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'AddToRouteActivity.')]
interface AddToRouteActivityInterface
{
    #[ActivityMethod(name: "AddToRoute")]
    public function addToRoute(
        string $taskUuid,
        int $startPointId,
        int $endPointId,
    ): array;
}
