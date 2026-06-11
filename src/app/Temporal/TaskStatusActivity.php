<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Facades\CentrifugoFacade;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskStatusActivity implements TaskStatusActivityInterface
{
    public function updateStatus(string $taskUuid, string $status): void
    {
        try {
            $task = Task::where('uuid', $taskUuid)->first();

            if (! $task) {
                Log::warning('Task not found for status update', ['taskUuid' => $taskUuid]);

                return;
            }

            DB::transaction(static function () use ($task, $status) {
                $task->status = $status;
                $task->save();
            });

            try {
                CentrifugoFacade::publish('task_status', [
                    'uuid' => $task->uuid,
                    'status' => $status,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to broadcast task status', ['uuid' => $taskUuid, 'error' => $e->getMessage()]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to update task status', ['taskUuid' => $taskUuid, 'status' => $status, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
