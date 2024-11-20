<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface AssignOrderWorkflowInterface
{
    #[WorkflowMethod(name: "AssignOrderActivity.assign")]
    public function assign(array $orderUuids, string $taskUuid);
}
