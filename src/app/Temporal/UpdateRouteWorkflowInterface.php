<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface UpdateRouteWorkflowInterface
{
    #[WorkflowMethod(name: "UpdateRoute.Update")]
    public function update(string $taskUuid, array $points);
}
