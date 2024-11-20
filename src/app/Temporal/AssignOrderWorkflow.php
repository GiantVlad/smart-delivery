<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Order;
use App\Models\Task;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class AssignOrderWorkflow implements AssignOrderWorkflowInterface
{
    private AssignOrderActivityInterface $assignOrderActivity;
    private $notifyCustomerActivity;

    private $notifyCouirierActivity;

    public function __construct()
    {
        $this->assignOrderActivity = Workflow::newActivityStub(
            AssignOrderActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->notifyCustomerActivity = Workflow::newActivityStub(
            NotifyOrderCreatedActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(5))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->notifyCouirierActivity = Workflow::newActivityStub(
            NotifyTaskCreatedActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(5))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );
    }

    public function assign(array $orderUuids, string $taskUuid): \Generator
    {
        $promises = [];
        foreach ($orderUuids as $orderUuid) {
            $order = Order::where('uuid', $orderUuid)->firstOrFail();
            $customerUuid = $order->customer->uuid;
            $promises[] = Workflow::async(function () use ($orderUuid, $taskUuid) {
                return $this->assignOrderActivity->assignOrder($orderUuid, $taskUuid);
            });
            $promises[] = Workflow::async(function () use ($orderUuid, $customerUuid) {
                return $this->notifyCustomerActivity->notifyOrderCreated($customerUuid, $orderUuid);
            });
        }

        foreach ($promises as $activity) {
            yield $activity;
        }
        $task = Task::where('uuid', $taskUuid)->firstOrFail();

        yield $this->notifyCouirierActivity->notifyTaskCreated($task->courier->uuid, $taskUuid);
    }
}
