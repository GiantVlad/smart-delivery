<?php

declare(strict_types=1);

namespace App\Temporal;

use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\SignalMethod;

#[WorkflowInterface]
interface OrderStatusHandlerWorkflowInterface
{
    #[WorkflowMethod(name: "OrderStatusHandler.Run")]
    public function run(): Generator;

    #[SignalMethod(name: "OrderStatusHandler.UpdateStatus")]
    public function updateStatus(
        string $orderUuid,
        string $status,
    ): void;

    #[SignalMethod(name: "OrderStatusHandler.Exit")]
    public function exit(): void;
}
