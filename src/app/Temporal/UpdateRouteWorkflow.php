<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class UpdateRouteWorkflow implements UpdateRouteWorkflowInterface
{
    private $updateRouteActivity;
    private $notifyActivity;

    public function __construct()
    {
        $this->updateRouteActivity = Workflow::newActivityStub(
            UpdateRouteActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(10))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(2))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

//        $this->notifyActivity = Workflow::newActivityStub(
//            NotifyTaskCreatedActivity::class,
//            ActivityOptions::new()
//                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
//                ->withRetryOptions(
//                    RetryOptions::new()
//                        ->withInitialInterval(CarbonInterval::seconds(1))
//                        ->withMaximumAttempts(3)
//                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
//                )
//        );
    }

    public function update(string $taskUuid, array $points): \Generator
    {
        yield $this->updateRouteActivity->updateRoute($taskUuid, $points);
        // yield $this->notifyActivity->notifyTaskCreated($taskUuid);
    }
}
