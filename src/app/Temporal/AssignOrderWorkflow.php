<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\AssignOrderDto;
use App\Dto\OrderDto;
use App\Notifications\OrderAssignedNotification;
use App\Notifications\OrderAssignedToTaskNotification;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class AssignOrderWorkflow implements AssignOrderWorkflowInterface
{
    private $assignOrderActivity;

    private $addToRouteActivity;
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

    public function assign(AssignOrderDto $assignOrderDto): \Generator
    {
        $promises = [];
        foreach ($assignOrderDto->orderCustomerUuids as $orderUuid => $customerUuid) {
            $promises[] = Workflow::async(function () use ($orderUuid, $assignOrderDto) {
                return $this->assignOrderActivity->assignOrder($orderUuid, $assignOrderDto->taskUuid);
            });
            $promises[] = Workflow::async(function () use ($orderUuid, $customerUuid) {
                return $this->notifyCustomerActivity->notifyCustomer(
                    $customerUuid,
                    $orderUuid,
                    OrderAssignedNotification::class,
                );
            });
        }

        foreach ($promises as $activity) {
            $res = yield $activity;
            if ($res instanceof OrderDto) {
                yield $this->addToRouteActivity->addToRoute($assignOrderDto->taskUuid, $res->startPointId, $res->endPointId);
            }
        }

        yield $this->notifyCouirierActivity->notifyCourier(
            $assignOrderDto->courierUuid,
            $assignOrderDto->taskUuid,
            OrderAssignedToTaskNotification::class,
            ['orderUuids' => array_keys($assignOrderDto->orderCustomerUuids)],
        );
    }
}
