<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateTaskActivity.')]
interface NotifyTaskCreatedActivityInterface
{
    #[ActivityMethod(name: "NotifyTaskCreated")]
    public function notifyTaskCreated(
        string $courierUuid,
        string $taskUuid,
    ): string;
}
