<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\TaskDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'TaskFinishedActivity.')]
interface TaskFinishedActivityInterface
{
    #[ActivityMethod(name: "FinishTask")]
    public function finishTask(
        TaskDto $taskDto
    ): string;
}
