<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ResetWorkflows extends Command
{
    protected $signature = 'temporal:reset-workflows
                            {--workflow-type= : Workflow type name (e.g., Task.Run, Order.Run)}
                            {--all : Reset all running workflow types (Task.Run, Order.Run)}
                            {--batch-size=50 : Number of workflows to reset per batch}
                            {--dry-run : Show what would be reset without actually resetting}
                            {--reason=Deployment reset : Reason for the reset}';

    protected $description = 'Reset running Temporal workflows to apply new code changes';

    private const WORKFLOW_TYPES = ['Task.Run', 'Order.Run'];

    public function handle(): int
    {
        $workflowType = $this->option('workflow-type');
        $all = $this->option('all');
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');
        $reason = $this->option('reason');

        if (! $workflowType && ! $all) {
            $this->error('Please specify --workflow-type or --all');

            return 1;
        }

        $types = $all ? self::WORKFLOW_TYPES : [$workflowType];

        foreach ($types as $type) {
            $this->info("Processing workflow type: {$type}");
            $this->resetWorkflowsOfType($type, $batchSize, $dryRun, $reason);
        }

        $this->info('Done.');

        return 0;
    }

    private function resetWorkflowsOfType(string $type, int $batchSize, bool $dryRun, string $reason): void
    {
        $workflows = $this->listRunningWorkflows($type, $batchSize);

        if (empty($workflows)) {
            $this->warn("  No running workflows found for type: {$type}");

            return;
        }

        $this->info('  Found '.count($workflows).' running workflow(s)');

        foreach ($workflows as $wf) {
            $workflowId = $wf['execution']['workflowId'];
            $runId = $wf['execution']['runId'];

            if ($dryRun) {
                $this->line("  [DRY RUN] Would reset: {$workflowId} (run: {$runId})");

                continue;
            }

            $this->line("  Resetting: {$workflowId} (run: {$runId})...");

            try {
                $this->resetWorkflow($workflowId, $runId, $reason);
                $this->line('    ✓ Reset successful');
            } catch (\Throwable $e) {
                $this->error('    ✗ Reset failed: '.$e->getMessage());
            }
        }
    }

    private function listRunningWorkflows(string $type, int $limit): array
    {
        $process = new Process([
            'docker', 'exec', 'temporal-admin-tools',
            'tctl', '--namespace', 'default',
            'workflow', 'list',
            '--open',
            '--workflow_type', $type,
            '--pagesize', (string) $limit,
            '--print_json',
        ]);

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('  Failed to list workflows: '.$process->getErrorOutput());

            return [];
        }

        $output = $process->getOutput();
        if (empty(trim($output))) {
            return [];
        }

        return json_decode($output, true) ?? [];
    }

    private function resetWorkflow(string $workflowId, string $runId, string $reason): void
    {
        $process = new Process([
            'docker', 'exec', 'temporal-admin-tools',
            'tctl', '--namespace', 'default',
            'workflow', 'reset',
            '--workflow_id', $workflowId,
            '--run_id', $runId,
            '--reset_type', 'FirstWorkflowTask',
            '--reason', $reason,
        ]);

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }
    }
}
