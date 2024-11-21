<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'RemoveFromRouteActivity.')]
interface RemoveFromRouteActivityInterface
{
    #[ActivityMethod(name: "RemoveFromRoute")]
    public function removeFromRoute(
        string $taskUuid,
        int $startPointId,
        int $endPointId,
    ): array;
}
