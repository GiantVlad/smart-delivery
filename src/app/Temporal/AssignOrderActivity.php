<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Support\Str;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class AssignOrderActivity implements AssignOrderActivityInterface
{
    public function assignOrder(string $orderUuid, string $taskUuid,): string
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();
        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        $order->task_id = $task->id;
        $order->status = OrderStatusEnum::ASSIGNED->value;
        $order->save();

        return $order->uuid;
    }
}
