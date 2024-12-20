<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\AssignOrderDto;
use App\Dto\OrderDto;
use App\Notifications\OrderUnaAssignedFromTaskNotification;
use App\Notifications\OrderUnassignedNotification;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class UnassignOrderWorkflow implements UnassignOrderWorkflowInterface
{
    private $unAssignOrderActivity;

    private $removeFromRouteActivity;
    private $notifyCustomerActivity;

    private $notifyCouirierActivity;

    public function __construct()
    {
        $this->unAssignOrderActivity = Workflow::newActivityStub(
            UnassignOrderActivityInterface::class,
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
            NotifyCustomerActivityInterface::class,
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
            NotifyCourierActivityInterface::class,
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

        yield $this->notifyCustomerActivity->notifyCustomer(
            $orderDto->customerUuid,
            $orderDto->uuid,
            OrderUnassignedNotification::class,
        );

        yield $this->notifyCouirierActivity->notifyCourier(
            $assignOrderDto->courierUuid,
            $assignOrderDto->taskUuid,
            OrderUnaAssignedFromTaskNotification::class,
            ['orderUuid' => $orderDto->uuid],
        );
    }
}
