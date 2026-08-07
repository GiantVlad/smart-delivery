<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Dto\CreateTaskCommand;
use App\Models\Task;
use App\Temporal\TaskWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;

class RestartTaskWorkflow extends Command
{
    protected $signature = 'task:restart-workflow
                            {task-uuid : The UUID of the task to restart the workflow for}';

    protected $description = 'Restart a Temporal workflow for an existing task (e.g. after termination)';

    public function handle(WorkflowClientInterface $workflowClient): int
    {
        $taskUuid = $this->argument('task-uuid');

        $task = Task::where('uuid', $taskUuid)->first();

        if (! $task) {
            $this->error("Task not found: {$taskUuid}");

            return 1;
        }

        $orders = \App\Models\Order::where('task_id', $task->id)->pluck('uuid')->toArray();

        $this->info("Restarting workflow for task: {$taskUuid}");
        $this->info("Status: {$task->status}");
        $this->info("Courier ID: {$task->courier_id}");
        $this->info('Orders: '.count($orders));

        try {
            $workflow = $workflowClient->newWorkflowStub(
                TaskWorkflowInterface::class,
                WorkflowOptions::new()
                    ->withWorkflowId('task:'.$task->uuid)
                    ->withWorkflowExecutionTimeout(CarbonInterval::days(30))
            );

            $workflowClient->start($workflow, new CreateTaskCommand($task->uuid, $task->courier_id, $orders));

            $this->info('✓ New workflow started successfully');

            return 0;
        } catch (\Throwable $e) {
            $this->error('✗ Failed to start workflow: '.$e->getMessage());

            return 1;
        }
    }
}
