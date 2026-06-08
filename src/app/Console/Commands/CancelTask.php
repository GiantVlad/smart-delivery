<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\TaskCancelService;
use Illuminate\Console\Command;

class CancelTask extends Command
{
    protected $signature = 'task:cancel {taskUuid}';

    protected $description = 'Cancel a task and clean up associated orders and routes';

    public function handle(TaskCancelService $cancelService)
    {
        $taskUuid = $this->argument('taskUuid');
        $task = Task::where('uuid', $taskUuid)->first();

        if (! $task) {
            $this->error('Task not found.');

            return 1;
        }

        $cancelService->cancel($task);

        $this->info("Task {$taskUuid} cancellation process has been triggered.");

        return 0;
    }
}
