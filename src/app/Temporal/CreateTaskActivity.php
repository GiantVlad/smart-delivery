<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateTaskDto;
use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Temporal\Activity;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\IllegalStateException;

class CreateTaskActivity implements CreateTaskActivityInterface
{
    public function createTask(CreateTaskDto $taskDto): string
    {
        $courier = Courier::where('uuid', $taskDto->courierUuid)->firstOrFail();
        $orders = Order::whereIn('uuid', $taskDto->orderUuids)->get();
        $task = new Task();

        DB::transaction(static function () use ($orders, $courier, $task) {
            $task->courier()->associate($courier);
            $task->uuid = Str::uuid()->toString();
            $task->status = TaskStatusEnum::CREATED->value;
            $task->save();
            foreach ($orders as $order) {
                $order->task()->associate($task);
                $order->save();
            }
        });

        return $task->uuid ?? '';
    }
}
