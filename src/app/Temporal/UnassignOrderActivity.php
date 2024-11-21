<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class UnassignOrderActivity implements UnassignOrderActivityInterface
{
    public function unassignOrder(string $orderUuid,): OrderDto
    {
        $order = Order::where('uuid', $orderUuid)->first();
        $order->task_id = null;
        $order->status = OrderStatusEnum::ACCEPTED->value;
        $order->save();

        return new OrderDto(
            $order->customer->uuid,
            $order->unit_type,
            $order->start_point_id,
            $order->end_point_id,
            $order->uuid
        );
    }
}
