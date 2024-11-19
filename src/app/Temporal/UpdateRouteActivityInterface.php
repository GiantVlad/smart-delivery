<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'UpdateRouteActivity.')]
interface UpdateRouteActivityInterface
{
    #[ActivityMethod(name: "UpdateRoute")]
    public function updateRoute(
        string $taskUuid,
        array $points,
    ): array;
}
