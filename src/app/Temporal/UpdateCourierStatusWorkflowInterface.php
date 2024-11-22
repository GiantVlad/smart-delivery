<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\AssignOrderDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface UpdateCourierStatusWorkflowInterface
{
    #[WorkflowMethod(name: "UpdateCourierStatusActivity.update")]
    public function update(string $courierUuid, string $status);
}
