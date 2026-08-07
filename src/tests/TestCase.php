<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Temporal\Client\WorkflowClientInterface;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(WorkflowClientInterface::class, function ($mock) {
            $mock->shouldReceive('start')->andReturn(new class () {
                public function getExecution()
                {
                    return null;
                }
            });
            $mock->shouldReceive('signalWithStart')->andReturn(new class () {
                public function getExecution()
                {
                    return null;
                }
            });
            $mock->shouldReceive('newRunningWorkflowStub')->andReturn(new class () {
                public function __call($method, $args)
                {
                    return null;
                }
            });
            $mock->shouldReceive('newUntypedRunningWorkflowStub')->andReturn(new class () {
                public function __call($method, $args)
                {
                    return null;
                }
            });
        });
    }
}
