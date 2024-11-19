<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace App\Temporal;

use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;

class OrderStatusHandlerWorkflow implements OrderStatusHandlerWorkflowInterface
{
    private $updateOrderActivity;
    /**
     * @var array|string[]
     */
    private array $input = [];
    private bool $exit = false;

    public function __construct()
    {
        $this->updateOrderActivity = Workflow::newActivityStub(
            UpdateOrderStatusActivityInterface::class,
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

    public function run(): \Generator
    {
        while (true) {
            yield Workflow::await(fn() => $this->input !== [] || $this->exit);
            if ($this->exit) {
                return;
            }
            $orderUuid = $this->input['orderUuid'];
            $status = $this->input['status'];
            $this->input = [];

            yield $this->updateOrderActivity->updateOrderStatus($orderUuid, $status);
        }
    }

    public function updateStatus(string $orderUuid, string $status): void
    {
        $this->input = ['orderUuid' => $orderUuid, 'status' => $status,];
    }

    public function exit(): void
    {
        $this->exit = true;
    }
}
