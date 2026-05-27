<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\TaskDto;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class TaskWorkflow implements TaskWorkflowInterface
{
    private $createTaskActivity;

    private $createRouteActivity;

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

        $this->createRouteActivity = Workflow::newActivityStub(
            CreateRouteActivityInterface::class,
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

    public function create(TaskDto $taskDto): \Generator
    {
        $taskUuid = yield $this->createTaskActivity->createTask($taskDto);

        yield $this->createRouteActivity->createRoute($taskUuid);
    }
}
