<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Temporal\OrderForErpObserverWorkflowInterface;
use Mockery;
use Mockery\MockInterface;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowStubInterface;
use Tests\TestCase;

class OrderErpObserverWorkflowTest extends TestCase
{
    public function test_disabled_erp_acceptance_terminates_the_observer_workflow(): void
    {
        config()->set('features.erp_order_acceptance_enabled', false);

        $workflow = Mockery::mock(WorkflowStubInterface::class);
        $workflow->shouldReceive('terminate')
            ->once()
            ->with('ERP order acceptance is disabled');

        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock) use ($workflow): void {
            $mock->shouldReceive('newUntypedRunningWorkflowStub')
                ->once()
                ->with(OrderForErpObserverWorkflowInterface::WORKFLOW_ID)
                ->andReturn($workflow);
            $mock->shouldNotReceive('newWorkflowStub');
            $mock->shouldNotReceive('start');
        });

        $this->artisan('wf-order-erp-observer:start')
            ->expectsOutput('ERP order acceptance is disabled. The observer workflow was terminated.')
            ->assertSuccessful();
    }
}
