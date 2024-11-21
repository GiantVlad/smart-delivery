<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\AssignOrderDto;
use App\Dto\OrderDto;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class UnassignOrderWorkflow implements UnAssignOrderWorkflowInterface
{
    private $unAssignOrderActivity;

    private $removeFromRouteActivity;
    private $notifyCustomerActivity;

    private $notifyCouirierActivity;

    public function __construct()
    {
        $this->unAssignOrderActivity = Workflow::newActivityStub(
            UnAssignOrderActivityInterface::class,
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

    public function unassign(AssignOrderDto $assignOrderDto): \Generator
    {
        /** @var OrderDto $orderDto */
        $orderDto = yield $this->unAssignOrderActivity->unassignOrder(
            array_key_first($assignOrderDto->orderCustomerUuids)
        );
        yield $this->removeFromRouteActivity->removeFromRoute(
            $assignOrderDto->taskUuid,
            $orderDto->startPointId,
            $orderDto->endPointId
        );
        yield $this->notifyCustomerActivity->notifyOrderCreated($orderDto->customerUuid, $orderDto->uuid);
        yield $this->notifyCouirierActivity->notifyTaskCreated($assignOrderDto->courierUuid, $assignOrderDto->taskUuid);
    }
}
