<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tprl;

use App\Models\Customer;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class CreateOrderWorkflow implements CreateOrderWorkflowInterface
{
    private $createOrderActivity;
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

    public function create(string $customerUuid, string $unitType): \Generator
    {
        $orderUuid = $this->createOrderActivity->createOrder($customerUuid, $unitType);

        yield $this->notifyActivity->notifyOrderCreated($customerUuid, yield $orderUuid);
    }
}
