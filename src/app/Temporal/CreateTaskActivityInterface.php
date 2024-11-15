<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateTaskDto;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateTaskActivity.')]
interface CreateTaskActivityInterface
{
    #[ActivityMethod(name: "CreateTask")]
    public function createTask(
        CreateTaskDto $taskDto
    ): string;
}
