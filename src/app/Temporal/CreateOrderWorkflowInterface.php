<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface CreateOrderWorkflowInterface
{
    #[WorkflowMethod(name: "CreateOrderActivity.create")]
    public function create(OrderDto $orderDto);
}
