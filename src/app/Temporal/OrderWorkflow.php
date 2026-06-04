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
            $this->applyAssign($this->pendingAssignTaskUuid);
            $this->pendingAssignTaskUuid = null;
            yield $this->projection->update($this->state);
        }

        if ($this->pendingUnassignTaskUuid !== null) {
            $this->applyUnassign($this->pendingUnassignTaskUuid);
            $this->pendingUnassignTaskUuid = null;
            yield $this->projection->update($this->state);
        }

        while ($this->pendingStatuses !== []) {
            $status = array_shift($this->pendingStatuses);
            $previousTaskUuid = $this->state->taskUuid;
            $this->applyStatus($status);
            yield $this->projection->update($this->state);

            if ($this->isTerminal($this->state->status) && $previousTaskUuid !== null) {
                $task = Workflow::newExternalWorkflowStub(
                    TaskWorkflowInterface::class,
                    new WorkflowExecution('task:'.$previousTaskUuid)
                );
                yield $task->orderReachedTerminal($this->state->orderUuid, $this->state->status);
            }
        }
    }

    private function applyAssign(string $taskUuid): void
    {
        if ($this->state->status !== OrderStatusEnum::ACCEPTED->value || $this->state->taskUuid !== null) {
            throw new \InvalidArgumentException('Order can only be assigned from accepted status without an active task.');
        }

        $this->state->taskUuid = $taskUuid;
        $this->state->status = OrderStatusEnum::ASSIGNED->value;
    }

    private function applyUnassign(string $taskUuid): void
    {
        if ($this->state->taskUuid !== $taskUuid) {
            throw new \InvalidArgumentException('Order is not assigned to the given task.');
        }

        $this->state->taskUuid = null;
        $this->state->status = OrderStatusEnum::ACCEPTED->value;
    }

    private function applyStatus(string $status): void
    {
        $next = OrderStatusEnum::tryFrom($status);
        $current = OrderStatusEnum::tryFrom($this->state->status);

        if ($next === null || $current === null) {
            throw new \InvalidArgumentException('Unknown order status.');
        }

        if (! in_array($next, OrderStatusEnum::canBeChangedTo($current), true)) {
            throw new \InvalidArgumentException("Order status {$this->state->status} can not be changed to {$status}.");
        }

        $this->state->status = $next->value;
        if ($next === OrderStatusEnum::CANCELED) {
            $this->state->taskUuid = null;
        }
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
