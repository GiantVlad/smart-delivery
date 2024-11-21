<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\AssignOrderDto;
use App\Dto\OrderDto;
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

    public function assign(AssignOrderDto $assignOrderDto): \Generator
    {
        $promises = [];
        foreach ($assignOrderDto->orderCustomerUuids as $orderUuid => $customerUuid) {
            $promises[] = Workflow::async(function () use ($orderUuid, $assignOrderDto) {
                return $this->assignOrderActivity->assignOrder($orderUuid, $assignOrderDto->taskUuid);
            });
            $promises[] = Workflow::async(function () use ($orderUuid, $customerUuid) {
                return $this->notifyCustomerActivity->notifyOrderCreated($customerUuid, $orderUuid);
            });
        }

        foreach ($promises as $activity) {
            $res = yield $activity;
            if ($res instanceof OrderDto) {
                yield $this->addToRouteActivity->addToRoute($assignOrderDto->taskUuid, $res->startPointId, $res->endPointId);
            }
        }

        yield $this->notifyCouirierActivity->notifyTaskCreated($assignOrderDto->courierUuid, $assignOrderDto->taskUuid);
    }
}
