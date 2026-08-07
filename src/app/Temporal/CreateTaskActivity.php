<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\TaskDto;
use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CreateTaskActivity implements CreateTaskActivityInterface
{
    public function createTask(TaskDto $taskDto): string
    {
        $courier = Courier::where('uuid', $taskDto->courierUuid)->firstOrFail();
        $task = new Task();

        DB::transaction(static function () use ($courier, $task, $taskDto) {
            $task->courier()->associate($courier);
            $task->uuid = $taskDto->taskUuid;
            $task->status = TaskStatusEnum::CREATED->value;
            $task->save();
        });

        return $task->uuid ?? '';
    }
}
