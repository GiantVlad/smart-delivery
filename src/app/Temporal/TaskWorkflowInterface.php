<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateTaskCommand;
use App\Dto\TaskWorkflowState;
use Temporal\Workflow\QueryMethod;
use Temporal\Workflow\SignalMethod;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface TaskWorkflowInterface
{
    #[WorkflowMethod(name: 'Task.Run')]
    public function run(CreateTaskCommand $command);

    #[SignalMethod(name: 'Task.AddOrders')]
    public function addOrders(array $orderUuids): void;

    #[SignalMethod(name: 'Task.RemoveOrder')]
    public function removeOrder(string $orderUuid): void;

    #[SignalMethod(name: 'Task.OrderReachedTerminal')]
    public function orderReachedTerminal(string $orderUuid, string $status): void;

    #[SignalMethod(name: 'Task.Cancel')]
    public function cancel(): void;

    #[QueryMethod(name: 'Task.GetState')]
    public function getState(): ?TaskWorkflowState;
}
