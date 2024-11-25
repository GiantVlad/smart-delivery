<?php

declare(strict_types=1);

namespace App\Temporal;


use App\Dto\TaskDto;
use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Notifications\TaskCreatedNotification;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowExecution;

class CreateTaskWorkflow implements CreateTaskWorkflowInterface
{
    private $createTaskActivity;
    private $updateCourierStatusActivity;
    private $createRouteActivity;
    private $notifyTaskActivity;

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

        $this->updateCourierStatusActivity= Workflow::newActivityStub(
            UpdateCourierStatusActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->notifyTaskActivity = Workflow::newActivityStub(
            NotifyCourierActivityInterface::class,
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
                ->withStartToCloseTimeout(CarbonInterval::seconds(10))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );
    }

    public function create(TaskDto $taskDto): \Generator
    {
        $taskUuidPromise = $this->createTaskActivity->createTask($taskDto);
        $taskUuid = yield $taskUuidPromise;

        $notificationPr = Workflow::async(function () use ($taskDto, $taskUuid) {
            return $this->notifyTaskActivity->notifyCourier(
                $taskDto->courierUuid,
                $taskUuid,
                TaskCreatedNotification::class
            );
        });

        $createRoutePr =  Workflow::async(function () use ($taskUuid) {
            return $this->createRouteActivity->createRoute($taskUuid);
        });

        $workflowOrderStatusHandler = Workflow::newExternalWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            new WorkflowExecution($taskDto->orderStatusWFId),
        );

        foreach ($taskDto->orderUuids as $orderUuid) {
            $workflowOrderStatusHandler->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value);
        }

        $updateCourierStatusPr =  Workflow::async(function () use ($taskDto) {
            return $this->updateCourierStatusActivity->updateCourierStatus(
                $taskDto->courierUuid,
                CourierStatusEnum::OT->value
            );
        });

        yield $notificationPr;

        yield $createRoutePr;

        yield $updateCourierStatusPr;
    }
}
