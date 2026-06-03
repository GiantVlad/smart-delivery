<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderCommand;
use App\Dto\OrderWorkflowState;
use Temporal\Workflow\QueryMethod;
use Temporal\Workflow\SignalMethod;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface OrderWorkflowInterface
{
    #[WorkflowMethod(name: 'Order.Run')]
    public function run(CreateOrderCommand $command);

    #[SignalMethod(name: 'Order.Confirm')]
    public function confirm(string $status): void;

    #[SignalMethod(name: 'Order.AssignToTask')]
    public function assignToTask(string $taskUuid): void;

    #[SignalMethod(name: 'Order.UnassignFromTask')]
    public function unassignFromTask(string $taskUuid): void;

    #[SignalMethod(name: 'Order.UpdateStatus')]
    public function updateStatus(string $status): void;

    #[QueryMethod(name: 'Order.GetState')]
    public function getState(): ?OrderWorkflowState;
}
