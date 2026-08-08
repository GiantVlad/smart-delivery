<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderCommand;
use App\Dto\OrderWorkflowState;
use App\Enums\OrderStatusEnum;
use App\Notifications\OrderCreatedNotification;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowExecution;

class OrderWorkflow implements OrderWorkflowInterface
{
    private $projection;

    private $createOrderErpActivity;

    private $notifyCustomerActivity;

    private ?OrderWorkflowState $state = null;

    private array $pendingStatuses = [];

    private ?string $pendingAssignTaskUuid = null;

    private ?string $pendingUnassignTaskUuid = null;

    public function __construct()
    {
        $this->projection = Workflow::newActivityStub(
            OrderProjectionActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->createOrderErpActivity = Workflow::newActivityStub(
            CreateOrderErpActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(10))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(3))
                        ->withMaximumAttempts(5)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->notifyCustomerActivity = Workflow::newActivityStub(
            NotifyCustomerActivityInterface::class,
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

    public function run(CreateOrderCommand $command): \Generator
    {
        $this->state = new OrderWorkflowState(
            orderUuid: $command->orderUuid,
            customerUuid: $command->customerUuid,
            status: OrderStatusEnum::NEW->value,
            taskUuid: null,
            unitType: $command->unitType,
            startPointId: $command->startPointId,
            endPointId: $command->endPointId,
            timeRanges: $command->timeRanges,
        );

        yield $this->projection->create($command);

        $createOrderErp = Workflow::async(function () use ($command) {
            return $this->createOrderErpActivity->createOrderInErp($command->orderUuid);
        });
        $notifyCustomer = Workflow::async(function () use ($command) {
            return $this->notifyCustomerActivity->notifyCustomer(
                $command->customerUuid,
                $command->orderUuid,
                OrderCreatedNotification::class,
            );
        });

        yield $createOrderErp;
        yield $notifyCustomer;

        while (! $this->isTerminal($this->state->status)) {
            yield Workflow::await(fn () => $this->hasPendingChange());
            yield from $this->flushPendingChanges();
        }
    }

    public function confirm(string $status): void
    {
        $this->pendingStatuses[] = $status;
    }

    public function assignToTask(string $taskUuid): void
    {
        $this->pendingAssignTaskUuid = $taskUuid;
    }

    public function unassignFromTask(string $taskUuid): void
    {
        $this->pendingUnassignTaskUuid = $taskUuid;
    }

    public function updateStatus(string $status): void
    {
        $this->pendingStatuses[] = $status;
    }

    public function getState(): ?OrderWorkflowState
    {
        return $this->state;
    }

    private function hasPendingChange(): bool
    {
        return $this->pendingStatuses !== []
            || $this->pendingAssignTaskUuid !== null
            || $this->pendingUnassignTaskUuid !== null;
    }

    private function flushPendingChanges(): \Generator
    {
        if ($this->pendingAssignTaskUuid !== null) {
            $changed = $this->applyAssign($this->pendingAssignTaskUuid);
            $this->pendingAssignTaskUuid = null;
            if ($changed) {
                yield $this->projection->update($this->state);
            }
        }

        if ($this->pendingUnassignTaskUuid !== null) {
            $changed = $this->applyUnassign($this->pendingUnassignTaskUuid);
            $this->pendingUnassignTaskUuid = null;
            if ($changed) {
                yield $this->projection->update($this->state);
            }
        }

        while ($this->pendingStatuses !== []) {
            $status = array_shift($this->pendingStatuses);
            $previousTaskUuid = $this->state->taskUuid;
            if (! $this->applyStatus($status)) {
                continue;
            }

            yield $this->projection->update($this->state);

            if ($this->isTerminal($this->state->status) && $previousTaskUuid !== null) {
                $terminalRouteVersion = yield Workflow::getVersion(
                    'order-terminal-route-points',
                    Workflow::DEFAULT_VERSION,
                    1,
                );
                $task = Workflow::newExternalWorkflowStub(
                    TaskWorkflowInterface::class,
                    new WorkflowExecution('task:'.$previousTaskUuid)
                );
                if ($terminalRouteVersion === Workflow::DEFAULT_VERSION) {
                    yield $task->orderReachedTerminal($this->state->orderUuid, $this->state->status);
                } else {
                    yield $task->orderReachedTerminal(
                        $this->state->orderUuid,
                        $this->state->status,
                        $this->state->startPointId,
                        $this->state->endPointId,
                    );
                }
            }

            if ($this->state->status === OrderStatusEnum::COLLECTED->value && $previousTaskUuid !== null) {
                try {
                    $task = Workflow::newExternalWorkflowStub(
                        TaskWorkflowInterface::class,
                        new WorkflowExecution('task:'.$previousTaskUuid)
                    );
                    yield $task->orderCollected($this->state->orderUuid);
                } catch (\Throwable $e) {
                    Log::warning('Failed to signal task order collected', [
                        'orderUuid' => $this->state->orderUuid,
                        'taskUuid' => $previousTaskUuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function applyAssign(string $taskUuid): bool
    {
        if ($this->state->taskUuid === $taskUuid && $this->state->status === OrderStatusEnum::ASSIGNED->value) {
            return false;
        }

        if ($this->state->status !== OrderStatusEnum::ACCEPTED->value || $this->state->taskUuid !== null) {
            return false;
        }

        $this->state->taskUuid = $taskUuid;
        $this->state->status = OrderStatusEnum::ASSIGNED->value;

        return true;
    }

    private function applyUnassign(string $taskUuid): bool
    {
        if ($this->state->taskUuid === null) {
            return false;
        }

        if ($this->state->taskUuid !== $taskUuid) {
            return false;
        }

        $this->state->taskUuid = null;
        $this->state->status = OrderStatusEnum::ACCEPTED->value;

        return true;
    }

    private function applyStatus(string $status): bool
    {
        $next = OrderStatusEnum::tryFrom($status);
        $current = OrderStatusEnum::tryFrom($this->state->status);

        if ($next === null || $current === null) {
            return false;
        }

        if ($next === $current) {
            return false;
        }

        if (! in_array($next, OrderStatusEnum::canBeChangedTo($current), true)) {
            return false;
        }

        $this->state->status = $next->value;
        if ($next === OrderStatusEnum::CANCELED) {
            $this->state->taskUuid = null;
        }

        return true;
    }

    private function isTerminal(string $status): bool
    {
        return in_array($status, [
            OrderStatusEnum::DELIVERED->value,
            OrderStatusEnum::CANCELED->value,
            OrderStatusEnum::DECLINED->value,
        ], true);
    }
}
