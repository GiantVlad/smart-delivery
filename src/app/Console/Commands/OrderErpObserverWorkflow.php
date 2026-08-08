<?php

namespace App\Console\Commands;

use App\Temporal\OrderForErpObserverWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\Client\WorkflowNotFoundException;

class OrderErpObserverWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wf-order-erp-observer:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start order for erp observer';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowClientInterface $workflowClient): void
    {
        if (! config('features.erp_order_acceptance_enabled')) {
            $this->stopObserverWorkflow($workflowClient);

            return;
        }

        try {
            $this->info('Starting <comment>OrderForErpObserverWorkflow</comment>... ');
            $workflow = $workflowClient->newWorkflowStub(
                OrderForErpObserverWorkflowInterface::class,
                WorkflowOptions::new()
                    ->withWorkflowId(OrderForErpObserverWorkflowInterface::WORKFLOW_ID)
                    // Execute the workflow every 3 minutes
                    ->withCronSchedule('*/3 * * * *')
                    // Run timeout limits duration of a single workflow invocation.
                    ->withWorkflowRunTimeout(CarbonInterval::minute())
            );

            $workflowClient->start($workflow);
            $this->info(
                sprintf(
                    'Started: WorkflowID=<fg=magenta>%s</fg=magenta>',
                    OrderForErpObserverWorkflowInterface::WORKFLOW_ID,
                )
            );
        } catch (\Throwable $exception) {
            $this->fail("Can't start OrderForErpObserverWorkflow. Caught exception: {$exception->getMessage()}");
        }
    }

    private function stopObserverWorkflow(WorkflowClientInterface $workflowClient): void
    {
        try {
            $workflow = $workflowClient->newUntypedRunningWorkflowStub(
                OrderForErpObserverWorkflowInterface::WORKFLOW_ID,
            );
            $workflow->terminate('ERP order acceptance is disabled');
            $this->info('ERP order acceptance is disabled. The observer workflow was terminated.');
        } catch (WorkflowNotFoundException) {
            $this->info('ERP order acceptance is disabled. The observer workflow is not running.');
        }
    }
}
