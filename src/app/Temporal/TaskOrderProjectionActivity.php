<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\Task;

class TaskOrderProjectionActivity implements TaskOrderProjectionActivityInterface
{
    public function attach(string $taskUuid, string $orderUuid): OrderDto
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();
        $order = Order::where('uuid', $orderUuid)->with('customer')->firstOrFail();
        $order->task_id = $task->id;
        $order->status = OrderStatusEnum::ASSIGNED->value;
        $order->save();

        return $this->dto($order);
    }

    public function detach(string $taskUuid, string $orderUuid): OrderDto
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();
        $order = Order::where('uuid', $orderUuid)->with('customer')->firstOrFail();
        if ((int) $order->task_id !== (int) $task->id) {
            throw new \InvalidArgumentException('Order is not attached to the given task projection.');
        }

        $order->task_id = null;
        $order->status = OrderStatusEnum::ACCEPTED->value;
        $order->save();

        return $this->dto($order);
    }

    private function dto(Order $order): OrderDto
    {
        return new OrderDto(
            customerUuid: $order->customer->uuid,
            unitType: $order->unit_type,
            startPointId: $order->start_point_id,
            endPointId: $order->end_point_id,
            timeRanges: $order->time_ranges ?? [],
            uuid: $order->uuid,
            status: $order->status,
        );
    }
}
