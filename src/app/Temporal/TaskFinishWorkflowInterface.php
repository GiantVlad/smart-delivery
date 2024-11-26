<?php

declare(strict_types=1);

namespace App\Temporal;


use App\Dto\TaskDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface TaskFinishWorkflowInterface
{
    #[WorkflowMethod(name: "TaskFinish.Finish")]
    public function finish(TaskDto $taskDto);
}
