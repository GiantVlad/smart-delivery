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
            NotifyTaskCreatedActivity::class,
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

    public function create(CreateTaskDto $taskDto): \Generator
    {
        $taskUuid = $this->createTaskActivity->createTask($taskDto);

        yield $this->notifyTaskActivity->notifyTaskCreated($taskDto->courierUuid, yield $taskUuid);

        $workflowOrderStatusHandler = Workflow::newExternalWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            new WorkflowExecution($taskDto->orderStatusWFId),
        );

        foreach ($taskDto->orderUuids as $orderUuid) {
            $workflowOrderStatusHandler->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value);
        }
    }
}
