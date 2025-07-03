<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\TaskDto;
use App\Enums\CourierStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Facades\CentrifugoFacade;
use App\Models\Courier;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Opekunov\Centrifugo\Exceptions\CentrifugoException;

class TaskFinishActivity implements TaskFinishedActivityInterface
{
    public function finishTask(TaskDto $taskDto): string
    {
        $courier = Courier::where('uuid', $taskDto->courierUuid)->firstOrFail();
        $task = Task::where('uuid', $taskDto->taskUuid)->firstOrFail();

        DB::transaction(static function () use ($courier, $task) {
            $courier->status = CourierStatusEnum::RD->value;
            $courier->save();
            $task->status = TaskStatusEnum::FINISHED->value;
            $task->save();
        });
        try {
            CentrifugoFacade::publish('courier_status', ['uuid' => $courier->uuid, 'status' => $courier->status]);
            CentrifugoFacade::publish('task_status', ['uuid' => $task->uuid, 'status' => $task->status]);
        } catch (CentrifugoException $e) {
            Log::error($e);
        }

        return $task->uuid;
    }
}
