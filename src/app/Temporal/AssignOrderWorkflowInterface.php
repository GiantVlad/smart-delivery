<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\AssignOrderDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface AssignOrderWorkflowInterface
{
    #[WorkflowMethod(name: "AssignOrder.Assign")]
    public function assign(AssignOrderDto $assignOrderDto);
}
