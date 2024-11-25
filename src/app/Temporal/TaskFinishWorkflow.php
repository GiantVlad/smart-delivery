<?php

declare(strict_types=1);

namespace App\Temporal;


use App\Dto\TaskDto;
use App\Enums\CourierStatusEnum;
use App\Notifications\TaskFinishedNotification;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowExecution;

class TaskFinishWorkflow implements TaskFinishWorkflowInterface
{
    private $taskFinishActivity;
    private $notifyTaskActivity;

    public function __construct()
    {
        $this->taskFinishActivity = Workflow::newActivityStub(
            TaskFinishedActivityInterface::class,
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
    }

    public function finish(TaskDto $taskDto): \Generator
    {
        $taskUuid = yield $this->taskFinishActivity->finishTask($taskDto);

        $updateCourierStatus = Workflow::newChildWorkflowStub(UpdateCourierStatusWorkflowInterface::class);

        $notificationPr = Workflow::async(function () use ($taskDto, $taskUuid) {
            return $this->notifyTaskActivity->notifyCourier(
                $taskDto->courierUuid,
                $taskUuid,
                TaskFinishedNotification::class
            );
        });

        yield $updateCourierStatus->update($taskDto->courierUuid, CourierStatusEnum::RD->value);

        yield $notificationPr;
    }
}
