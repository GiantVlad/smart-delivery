<?php

namespace App\Console\Commands;

use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Temporal\Client\WorkflowClientInterface;

class StartStatusHandlerWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wf-status-handler:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start status handler';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowClientInterface $workflowClient): void
    {
        try {
            $workflow = $workflowClient->newWorkflowStub(
                OrderStatusHandlerWorkflowInterface::class
            );

            $this->info("Starting <comment>OrderStatusHandlerWorkflow</comment>... ");

            $run = $workflowClient->start($workflow);
            $wfId =$run->getExecution()->getID();
            Cache::put(OrderStatusHandlerWorkflowInterface::WORKFLOW_STATUS_HANDLER_KEY, $wfId);

            $this->info(
                sprintf(
                    'Started: WorkflowID=<fg=magenta>%s</fg=magenta>',
                    $wfId,
                )
            );
        } catch (\Throwable $exception) {
            $this->fail("Can't start workflow status handler. Caught exception: {$exception->getMessage()}");
        }
    }
}
