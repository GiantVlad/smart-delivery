<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\TaskDto;
use App\Enums\OrderStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CreateTaskActivity implements CreateTaskActivityInterface
{
    public function createTask(TaskDto $taskDto): string
    {
        $courier = Courier::where('uuid', $taskDto->courierUuid)->firstOrFail();
        $orders = Order::whereIn('uuid', $taskDto->orderUuids)->get();
        $task = new Task;

        DB::transaction(static function () use ($orders, $courier, $task, $taskDto) {
            $task->courier()->associate($courier);
            $task->uuid = $taskDto->taskUuid;
            $task->status = TaskStatusEnum::CREATED->value;
            $task->save();
            foreach ($orders as $order) {
                $order->task_id = $task->id;
                $order->status = OrderStatusEnum::ASSIGNED->value;
                $order->save();
            }
        });

        return $task->uuid ?? '';
    }
}
