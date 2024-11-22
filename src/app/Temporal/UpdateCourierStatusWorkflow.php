<?php

declare(strict_types=1);

namespace App\Temporal;

use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class UpdateCourierStatusWorkflow implements UpdateCourierStatusWorkflowInterface
{
    private $updateCourierStatusActivity;

    public function __construct()
    {
        $this->updateCourierStatusActivity = Workflow::newActivityStub(
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
    }

    public function update(string $courierUuid, string $status): \Generator
    {
        yield $this->updateCourierStatusActivity->updateCourierStatus($courierUuid, $status);
    }
}
