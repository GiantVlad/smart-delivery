<?php

declare(strict_types=1);

namespace App\Temporal;


use App\Dto\CreateTaskDto;
use App\Enums\OrderStatusEnum;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowExecution;

class CreateTaskWorkflow implements CreateTaskWorkflowInterface
{
    private $createTaskActivity;
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

        $this->notifyTaskActivity = Workflow::newActivityStub(
            NotifyTaskCreatedActivityInterface::class,
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

    public function create(CreateTaskDto $taskDto): \Generator
    {
        $taskUuidPromise = $this->createTaskActivity->createTask($taskDto);
        $taskUuid = yield $taskUuidPromise;

        $notificationPr = Workflow::async(static function () use ($taskDto, $taskUuid) {
            return $this->notifyTaskActivity->notifyTaskCreated($taskDto->courierUuid, $taskUuid);
        });

        $createRoutePr =  Workflow::async(static function () use ($taskUuid) {
            return $this->createRouteActivity->createRoute($taskUuid);
        });

        yield $notificationPr;
        yield $createRoutePr;

        $workflowOrderStatusHandler = Workflow::newExternalWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            new WorkflowExecution($taskDto->orderStatusWFId),
        );

        foreach ($taskDto->orderUuids as $orderUuid) {
            $workflowOrderStatusHandler->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value);
        }
    }
}
