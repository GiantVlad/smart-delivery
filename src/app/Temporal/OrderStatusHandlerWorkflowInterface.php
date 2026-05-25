<?php

declare(strict_types=1);

namespace App\Temporal;

use Generator;
use Temporal\Workflow\SignalMethod;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface OrderStatusHandlerWorkflowInterface
{
    public const WORKFLOW_STATUS_HANDLER_KEY = 'workflow-status-handler-id';

    public const WORKFLOW_ID = 'workflow-status-handler-v1';

    #[WorkflowMethod(name: 'OrderStatusHandler.Run')]
    public function run(): Generator;

    #[SignalMethod(name: 'OrderStatusHandler.UpdateStatus')]
    public function updateStatus(
        string $orderUuid,
        string $status,
    ): void;

    #[SignalMethod(name: 'OrderStatusHandler.Exit')]
    public function exit(): void;
}
