<?php

declare(strict_types=1);

namespace Tprl;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utils\Command;

class StopOrderStatusHandlerCommand extends Command
{
    protected const NAME = 'stop-order-status-handler';
    protected const DESCRIPTION = 'Stop OrderStatusHandlerWorkflow';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            'aeb6d950-691e-472f-ab8d-7b1563d02c23',
        );

        $output->writeln("Stopping <comment>OrderStatusHandlerWorkflow</comment>... ");

        $workflow->exit();

        $output->writeln(
            'Stoped: WorkflowID=<fg=magenta>da616486-da7d-45fc-9c19-de0bfc7d9c24</fg=magenta>',
        );

        return self::SUCCESS;
    }
}
