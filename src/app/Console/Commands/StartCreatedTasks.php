<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use App\Temporal\TaskWorkflowInterface;
use Illuminate\Console\Command;
use Temporal\Client\WorkflowClientInterface;

class StartCreatedTasks extends Command
{
    protected $signature = 'tasks:start-created
                            {--task-uuid= : Start a specific task by UUID}
                            {--all : Start all tasks in created status}
                            {--dry-run : Show what would be started without actually starting}';

    protected $description = 'Send Task.Start signal to tasks stuck in created status after deployment';

    public function handle(WorkflowClientInterface $workflowClient): int
    {
        $taskUuid = $this->option('task-uuid');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if (! $taskUuid && ! $all) {
            $this->error('Please specify --task-uuid or --all');

            return 1;
        }

        if ($taskUuid) {
            $tasks = Task::where('uuid', $taskUuid)->where('status', TaskStatusEnum::CREATED->value)->get();
        } else {
            $tasks = Task::where('status', TaskStatusEnum::CREATED->value)->get();
        }

        if ($tasks->isEmpty()) {
            $this->warn('No tasks found in created status');

            return 0;
        }

        $this->info("Found {$tasks->count()} task(s) in created status");

        foreach ($tasks as $task) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would start: {$task->uuid}");

                continue;
            }

            $this->line("  Starting: {$task->uuid}...");

            try {
                $workflow = $workflowClient->newRunningWorkflowStub(
                    TaskWorkflowInterface::class,
                    'task:'.$task->uuid,
                );
                $workflow->start();
                $this->line('    ✓ Signal sent');
            } catch (\Throwable $e) {
                $this->error('    ✗ Failed: '.$e->getMessage());
            }
        }

        $this->info('Done.');

        return 0;
    }
}
