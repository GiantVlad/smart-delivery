<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface OrderForErpObserverWorkflowInterface
{
    public const WORKFLOW_ID = 'order-for-erp-observer-v1';
    #[WorkflowMethod(name: "OrderForErpObserver.Update")]
    public function update();
}
