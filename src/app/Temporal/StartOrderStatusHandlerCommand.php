<?php

declare(strict_types=1);

namespace App\Temporal;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utils\Command;

class StartOrderStatusHandlerCommand extends Command
{
    protected const NAME = 'handle-order-status';
    protected const DESCRIPTION = 'Execute OrderStatusHandlerWorkflow';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $workflow = $this->workflowClient->newWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class
        );

        $output->writeln("Starting <comment>OrderStatusHandlerWorkflow</comment>... ");

        // Start a workflow execution. Usually this is done from another program.
        $run = $this->workflowClient->start($workflow);

        $output->writeln(
            sprintf(
                'Started: WorkflowID=<fg=magenta>%s</fg=magenta>',
                $run->getExecution()->getID(),
            )
        );

        return self::SUCCESS;
    }
}
