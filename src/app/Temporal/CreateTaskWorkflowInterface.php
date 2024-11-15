<?php

declare(strict_types=1);

namespace App\Temporal;


use App\Dto\CreateTaskDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface CreateTaskWorkflowInterface
{
    #[WorkflowMethod(name: "CreateTaskActivity.Create")]
    public function create(CreateTaskDto $taskDto);
}
