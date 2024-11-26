<?php

namespace App\Console\Commands;

use App\Temporal\OrderForErpObserverWorkflowInterface;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;

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
        try {
            $this->info("Starting <comment>OrderForErpObserverWorkflow</comment>... ");
            $workflow = $workflowClient->newWorkflowStub(
                OrderForErpObserverWorkflowInterface::class,
                WorkflowOptions::new()
                    ->withWorkflowId(OrderForErpObserverWorkflowInterface::WORKFLOW_ID)
                    // Execute the workflow every 3 minutes
                    ->withCronSchedule('*/3 * * * *')
                    // Execution timeout limits total time. Cron will stop executing after this timeout.
                    ->withWorkflowExecutionTimeout(CarbonInterval::minutes(5))
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
}
