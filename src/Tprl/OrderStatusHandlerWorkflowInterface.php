<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tprl;

use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\SignalMethod;

#[WorkflowInterface]
interface OrderStatusHandlerWorkflowInterface
{
    #[WorkflowMethod(name: "OrderStatusHandler.Run")]
    public function run(): Generator;

    #[SignalMethod(name: "OrderStatusHandler.UpdateStatus")]
    public function updateStatus(
        string $orderUuid,
        string $status,
    ): void;

    #[SignalMethod(name: "OrderStatusHandler.Exit")]
    public function exit(): void;
}
