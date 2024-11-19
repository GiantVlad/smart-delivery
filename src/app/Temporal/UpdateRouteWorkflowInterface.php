<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface UpdateRouteWorkflowInterface
{
    #[WorkflowMethod(name: "UpdateRouteActivity.update")]
    public function update(string $taskUuid, array $points);
}
