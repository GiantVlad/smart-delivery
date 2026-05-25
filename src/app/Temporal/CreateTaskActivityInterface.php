<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\TaskDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateTaskActivity.')]
interface CreateTaskActivityInterface
{
    #[ActivityMethod(name: 'CreateTask')]
    public function createTask(
        TaskDto $taskDto
    ): string;
}
