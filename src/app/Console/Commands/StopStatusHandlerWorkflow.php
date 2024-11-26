<?php

namespace App\Console\Commands;

use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Console\Command;
use Temporal\Client\WorkflowClientInterface;

class StopStatusHandlerWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wf-status-handler:stop {wfId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop status handler current or by WF ID';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowClientInterface $workflowClient): void
    {
        try {
            $this->info("Finishing <comment>OrderStatusHandlerWorkflow</comment>... ");

            $workflow = $workflowClient->newRunningWorkflowStub(
                OrderStatusHandlerWorkflowInterface::class,
                OrderStatusHandlerWorkflowInterface::WORKFLOW_ID,
            );
            $workflow->exit();

            $this->info(
                sprintf(
                    'Finished: WorkflowID=<fg=magenta>%s</fg=magenta>',
                    OrderStatusHandlerWorkflowInterface::WORKFLOW_ID,
                )
            );
        } catch (\Throwable $exception) {
            $this->fail("Can't stop workflow status handler. Caught exception: {$exception->getMessage()}");
        }
    }
}
