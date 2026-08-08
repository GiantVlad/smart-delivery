<?php

declare(strict_types=1);

namespace Tests\Integration\Temporal;

use App\Dto\CreateOrderCommand;
use PHPUnit\Framework\TestCase;
use Temporal\DataConverter\DataConverter;

class CreateOrderCommandTest extends TestCase
{
    public function test_old_workflow_input_defaults_erp_acceptance_to_enabled(): void
    {
        $oldWorkflowInput = (object) [
            'orderUuid' => 'order-uuid',
            'customerUuid' => 'customer-uuid',
            'unitType' => 'Medium',
            'startPointId' => 1,
            'endPointId' => 2,
            'timeRanges' => [],
        ];
        $converter = DataConverter::createDefault();

        $command = $converter->fromPayload(
            $converter->toPayload($oldWorkflowInput),
            CreateOrderCommand::class,
        );

        $this->assertTrue($command->erpAcceptanceEnabled);
    }
}
