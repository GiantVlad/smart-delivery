<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateRouteActivity.')]
interface CreateRouteActivityInterface
{
    #[ActivityMethod(name: "CreateRoute")]
    public function createRoute(
        string $taskUuid,
    ): true;
}
