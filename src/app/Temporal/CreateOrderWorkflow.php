<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderDto;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class CreateOrderWorkflow implements CreateOrderWorkflowInterface
{
    private $createOrderActivity;
    private $createOrderErpActivity;
    private $notifyActivity;

    public function __construct()
    {
        $this->createOrderActivity = Workflow::newActivityStub(
            CreateOrderActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(3)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->createOrderErpActivity = Workflow::newActivityStub(
            CreateOrderErpActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(10))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(3))
                        ->withMaximumAttempts(5)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->notifyActivity = Workflow::newActivityStub(
            NotifyOrderCreatedActivity::class,
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

    public function create(CreateOrderDto $orderDto): \Generator
    {
        $orderUuid = $this->createOrderActivity->createOrder($orderDto);

        yield $this->createOrderErpActivity->createOrderInErp(yield $orderUuid);
        yield $this->notifyActivity->notifyOrderCreated($orderDto->customerUuid, yield $orderUuid);
    }
}
