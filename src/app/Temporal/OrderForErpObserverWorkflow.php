<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class OrderForErpObserverWorkflow implements OrderForErpObserverWorkflowInterface
{
    private $updateOrderErpActivity;

    public function __construct()
    {
        $this->updateOrderErpActivity = Workflow::newActivityStub(
            UpdateOrderErpActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(10))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(3))
                        ->withMaximumAttempts(5)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );
    }

    public function update(): \Generator
    {
        yield $this->updateOrderErpActivity->udateOrderInErp();
    }
}
