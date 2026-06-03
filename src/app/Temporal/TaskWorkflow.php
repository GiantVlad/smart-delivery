<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateTaskCommand;
use App\Dto\TaskDto;
use App\Dto\TaskWorkflowState;
use App\Enums\TaskStatusEnum;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowExecution;

class TaskWorkflow implements TaskWorkflowInterface
{
    private $createTaskActivity;

    private $createRouteActivity;

    private $taskFinishActivity;

    private $taskOrderProjectionActivity;

    private $addToRouteActivity;

    private $removeFromRouteActivity;

    private ?TaskWorkflowState $state = null;

    private array $pendingAddOrderUuids = [];

    private ?string $pendingRemoveOrderUuid = null;

    private array $pendingTerminalOrders = [];

    private bool $pendingCancel = false;

    public function __construct()
    {
        $this->createTaskActivity = Workflow::newActivityStub(
            CreateTaskActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->createRouteActivity = Workflow::newActivityStub(
            CreateRouteActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->taskFinishActivity = Workflow::newActivityStub(
            TaskFinishedActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->taskOrderProjectionActivity = Workflow::newActivityStub(
            TaskOrderProjectionActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->addToRouteActivity = Workflow::newActivityStub(
            AddToRouteActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->removeFromRouteActivity = Workflow::newActivityStub(
            RemoveFromRouteActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );
    }

    public function run(CreateTaskCommand $command): \Generator
    {
        $this->state = new TaskWorkflowState(
            taskUuid: $command->taskUuid,
            courierUuid: $command->courierUuid,
            status: TaskStatusEnum::CREATED->value,
            orderUuids: array_values(array_unique($command->orderUuids)),
            terminalOrders: [],
        );

        $taskDto = new TaskDto($command->courierUuid, $this->state->orderUuids, $command->taskUuid);
        $taskUuid = yield $this->createTaskActivity->createTask($taskDto);

        foreach ($this->state->orderUuids as $orderUuid) {
            yield $this->orderWorkflow($orderUuid)->assignToTask($taskUuid);
        }

        yield $this->createRouteActivity->createRoute($taskUuid);

        while ($this->state->status !== TaskStatusEnum::FINISHED->value && $this->state->status !== TaskStatusEnum::CANCELED->value) {
            yield Workflow::await(fn () => $this->hasPendingChange());
            yield from $this->flushPendingChanges();
        }
    }

    public function addOrders(array $orderUuids): void
    {
        $this->pendingAddOrderUuids = array_merge($this->pendingAddOrderUuids, $orderUuids);
    }

    public function removeOrder(string $orderUuid): void
    {
        $this->pendingRemoveOrderUuid = $orderUuid;
    }

    public function orderReachedTerminal(string $orderUuid, string $status): void
    {
        $this->pendingTerminalOrders[$orderUuid] = $status;
    }

    public function cancel(): void
    {
        $this->pendingCancel = true;
    }

    public function getState(): ?TaskWorkflowState
    {
        return $this->state;
    }

    private function hasPendingChange(): bool
    {
        return $this->pendingAddOrderUuids !== []
            || $this->pendingRemoveOrderUuid !== null
            || $this->pendingTerminalOrders !== []
            || $this->pendingCancel;
    }

    private function flushPendingChanges(): \Generator
    {
        if ($this->pendingCancel) {
            $this->pendingCancel = false;
            $this->state->status = TaskStatusEnum::CANCELED->value;
            yield $this->taskFinishActivity->finishTask(
                new TaskDto($this->state->courierUuid, [], $this->state->taskUuid),
                TaskStatusEnum::CANCELED->value
            );

            return;
        }

        if ($this->pendingAddOrderUuids !== []) {
            $orderUuids = array_values(array_unique($this->pendingAddOrderUuids));
            $this->pendingAddOrderUuids = [];

            foreach ($orderUuids as $orderUuid) {
                if (in_array($orderUuid, $this->state->orderUuids, true)) {
                    continue;
                }
                $this->state->orderUuids[] = $orderUuid;
                yield $this->orderWorkflow($orderUuid)->assignToTask($this->state->taskUuid);
                $order = yield $this->taskOrderProjectionActivity->attach($this->state->taskUuid, $orderUuid);
                yield $this->addToRouteActivity->addToRoute($this->state->taskUuid, $order->startPointId, $order->endPointId);
            }
        }

        if ($this->pendingRemoveOrderUuid !== null) {
            $orderUuid = $this->pendingRemoveOrderUuid;
            $this->pendingRemoveOrderUuid = null;

            if (in_array($orderUuid, $this->state->orderUuids, true)) {
                $this->state->orderUuids = array_values(array_diff($this->state->orderUuids, [$orderUuid]));
                unset($this->state->terminalOrders[$orderUuid]);
                yield $this->orderWorkflow($orderUuid)->unassignFromTask($this->state->taskUuid);
                $order = yield $this->taskOrderProjectionActivity->detach($this->state->taskUuid, $orderUuid);
                yield $this->removeFromRouteActivity->removeFromRoute($this->state->taskUuid, $order->startPointId, $order->endPointId);
            }
        }

        foreach ($this->pendingTerminalOrders as $orderUuid => $status) {
            if (in_array($orderUuid, $this->state->orderUuids, true)) {
                $this->state->terminalOrders[$orderUuid] = $status;
            }
            unset($this->pendingTerminalOrders[$orderUuid]);
        }

        if ($this->state->orderUuids !== [] && count($this->state->terminalOrders) === count($this->state->orderUuids)) {
            $this->state->status = TaskStatusEnum::FINISHED->value;
            yield $this->taskFinishActivity->finishTask(new TaskDto($this->state->courierUuid, [], $this->state->taskUuid));
        }
    }

    private function orderWorkflow(string $orderUuid): OrderWorkflowInterface
    {
        return Workflow::newExternalWorkflowStub(
            OrderWorkflowInterface::class,
            new WorkflowExecution('order:'.$orderUuid)
        );
    }
}
