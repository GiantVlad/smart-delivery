<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateTaskCommand;
use App\Dto\TaskDto;
use App\Dto\TaskWorkflowState;
use App\Enums\TaskStatusEnum;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Log;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Exception\TemporalException;
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

    private $taskStatusActivity;

    private ?TaskWorkflowState $state = null;

    private array $pendingAddOrderUuids = [];

    private ?string $pendingRemoveOrderUuid = null;

    private array $pendingTerminalOrders = [];

    private array $pendingCollectedOrders = [];

    private bool $pendingStart = false;

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

        $this->taskStatusActivity = Workflow::newActivityStub(
            TaskStatusActivityInterface::class,
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
            yield $this->taskOrderProjectionActivity->attach($taskUuid, $orderUuid);
            try {
                yield $this->orderWorkflow($orderUuid)->assignToTask($taskUuid);
            } catch (TemporalException $e) {
                Log::warning('Failed to signal order workflow', [
                    'orderUuid' => $orderUuid,
                    'taskUuid' => $taskUuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        yield $this->createRouteActivity->createRoute($taskUuid, $this->state->orderUuids);

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

    public function orderCollected(string $orderUuid): void
    {
        $this->pendingCollectedOrders[$orderUuid] = true;
    }

    public function start(): void
    {
        Log::info('TaskWorkflow: start() signal received', ['taskUuid' => $this->state->taskUuid ?? 'unknown']);
        $this->pendingStart = true;
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
            || $this->pendingCollectedOrders !== []
            || $this->pendingStart
            || $this->pendingCancel;
    }

    private function flushPendingChanges(): \Generator
    {
        if ($this->pendingCancel) {
            $this->pendingCancel = false;

            foreach ($this->state->orderUuids as $orderUuid) {
                if (array_key_exists($orderUuid, $this->state->terminalOrders)) {
                    continue;
                }

                try {
                    yield $this->orderWorkflow($orderUuid)->unassignFromTask($this->state->taskUuid);
                } catch (TemporalException $e) {
                    Log::warning('Failed to signal order workflow on cancel', [
                        'orderUuid' => $orderUuid,
                        'taskUuid' => $this->state->taskUuid,
                        'error' => $e->getMessage(),
                    ]);
                }
                $order = yield $this->taskOrderProjectionActivity->detach($this->state->taskUuid, $orderUuid);
                yield $this->removeFromRouteActivity->removeFromRoute($this->state->taskUuid, $order->startPointId, $order->endPointId);
            }

            $this->state->status = TaskStatusEnum::CANCELED->value;
            yield $this->taskFinishActivity->finishTask(
                new TaskDto($this->state->courierUuid, [], $this->state->taskUuid),
                TaskStatusEnum::CANCELED->value
            );

            return;
        }

        if ($this->pendingStart) {
            $this->pendingStart = false;
            Log::info('TaskWorkflow: processing pendingStart', ['taskUuid' => $this->state->taskUuid, 'status' => $this->state->status]);
            if ($this->state->status === TaskStatusEnum::CREATED->value) {
                $this->state->status = TaskStatusEnum::STARTED->value;
                Log::info('TaskWorkflow: updating status to STARTED', ['taskUuid' => $this->state->taskUuid]);
                yield $this->taskStatusActivity->updateStatus($this->state->taskUuid, TaskStatusEnum::STARTED->value);
                Log::info('TaskWorkflow: TaskStatusActivity dispatched', ['taskUuid' => $this->state->taskUuid]);
            }
        }

        if ($this->pendingAddOrderUuids !== []) {
            $orderUuids = array_values(array_unique($this->pendingAddOrderUuids));
            $this->pendingAddOrderUuids = [];

            foreach ($orderUuids as $orderUuid) {
                if (in_array($orderUuid, $this->state->orderUuids, true)) {
                    continue;
                }
                $this->state->orderUuids[] = $orderUuid;
                $order = yield $this->taskOrderProjectionActivity->attach($this->state->taskUuid, $orderUuid);
                try {
                    yield $this->orderWorkflow($orderUuid)->assignToTask($this->state->taskUuid);
                } catch (TemporalException $e) {
                    Log::warning('Failed to signal order workflow on add', [
                        'orderUuid' => $orderUuid,
                        'taskUuid' => $this->state->taskUuid,
                        'error' => $e->getMessage(),
                    ]);
                }
                yield $this->addToRouteActivity->addToRoute($this->state->taskUuid, $order->startPointId, $order->endPointId);
            }
        }

        if ($this->pendingRemoveOrderUuid !== null) {
            $orderUuid = $this->pendingRemoveOrderUuid;
            $this->pendingRemoveOrderUuid = null;

            if (in_array($orderUuid, $this->state->orderUuids, true)) {
                $this->state->orderUuids = array_values(array_diff($this->state->orderUuids, [$orderUuid]));
                unset($this->state->terminalOrders[$orderUuid]);
                try {
                    yield $this->orderWorkflow($orderUuid)->unassignFromTask($this->state->taskUuid);
                } catch (TemporalException $e) {
                    Log::warning('Failed to signal order workflow on remove', [
                        'orderUuid' => $orderUuid,
                        'taskUuid' => $this->state->taskUuid,
                        'error' => $e->getMessage(),
                    ]);
                }
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

        foreach ($this->pendingCollectedOrders as $orderUuid => $val) {
            if (in_array($orderUuid, $this->state->orderUuids, true) && $this->state->status === TaskStatusEnum::CREATED->value) {
                $this->state->status = TaskStatusEnum::STARTED->value;
                yield $this->taskStatusActivity->updateStatus($this->state->taskUuid, TaskStatusEnum::STARTED->value);
            }
            unset($this->pendingCollectedOrders[$orderUuid]);
        }

        if ($this->state->orderUuids !== [] && count($this->state->terminalOrders) === count($this->state->orderUuids)) {
            $this->state->status = TaskStatusEnum::FINISHED->value;
            yield $this->taskFinishActivity->finishTask(new TaskDto($this->state->courierUuid, [], $this->state->taskUuid));
        }
    }

    private function orderWorkflow(string $orderUuid): object
    {
        return Workflow::newExternalWorkflowStub(
            OrderWorkflowInterface::class,
            new WorkflowExecution('order:'.$orderUuid)
        );
    }
}
