<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Facades\CentrifugoFacade;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class AssignOrderActivity implements AssignOrderActivityInterface
{
    public function assignOrder(string $orderUuid, string $taskUuid): OrderDto
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();
        $order = Order::where('uuid', $orderUuid)->with('customer')->firstOrFail();
        $order->task_id = $task->id;
        $order->status = OrderStatusEnum::ASSIGNED->value;
        $order->save();

        try {
            CentrifugoFacade::publish('order_status', ['order' => $order->uuid, 'status' => $order->status]);
        } catch (\Throwable $error) {
            Log::error('Failed to publish order_status for assign', [
                'order' => $order->uuid,
                'status' => $order->status,
                'error' => $error->getMessage(),
            ]);
        }

        return new OrderDto(
            $order->customer->uuid,
            $order->unit_type,
            $order->start_point_id,
            $order->end_point_id,
            $order->uuid
        );
    }
}
