<?php

namespace App\Console\Commands;

use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Support\Facades\Cache;
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
    protected $description = 'Start status handler';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowClientInterface $workflowClient): void
    {
        try {
            if (! ($wfId =$this->argument('wfId'))) {
                $wfId = Cache::get(OrderStatusHandlerWorkflowInterface::WORKFLOW_STATUS_HANDLER_KEY);
            }

            if ($wfId) {
                $this->fatal("The WORKFLOW ID undefined.");
            }
            $this->info("Finishing <comment>OrderStatusHandlerWorkflow</comment>... ");

            $workflow = $workflowClient->newRunningWorkflowStub(
                OrderStatusHandlerWorkflowInterface::class,
                $wfId,
            );
            $workflow->exit();

            $this->info(
                sprintf(
                    'Finished: WorkflowID=<fg=magenta>%s</fg=magenta>',
                    $wfId,
                )
            );
        } catch (\Throwable $exception) {
            $this->fatal("Can't stop workflow status handler. Caught exception: {$exception->getMessage()}");
        }
    }
}
