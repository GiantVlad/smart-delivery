<?php

declare(strict_types=1);

namespace App\Temporal;


use App\Dto\TaskDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface CreateTaskWorkflowInterface
{
    #[WorkflowMethod(name: "CreateTask.Create")]
    public function create(TaskDto $taskDto);
}
