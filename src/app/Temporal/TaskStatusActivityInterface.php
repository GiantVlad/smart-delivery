<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'TaskStatusActivity.')]
interface TaskStatusActivityInterface
{
    #[ActivityMethod(name: 'UpdateStatus')]
    public function updateStatus(string $taskUuid, string $status): void;
}
