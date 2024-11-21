<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\Task;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class AssignOrderActivity implements AssignOrderActivityInterface
{
    public function assignOrder(string $orderUuid, string $taskUuid,): OrderDto
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();
        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        $order->task_id = $task->id;
        $order->status = OrderStatusEnum::ASSIGNED->value;
        $order->save();

        return new OrderDto(
            $order->customerUuid,
            $order->unit_type,
            $order->strart_point_id,
            $order->end_point_id,
            $order->uuid
        );
    }
}
