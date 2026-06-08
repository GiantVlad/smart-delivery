<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Route;
use App\Models\Task;
use App\Models\User;
use App\Enums\OrderStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\CourierStatusEnum;
use App\Temporal\TaskWorkflowInterface;
use App\Temporal\OrderWorkflowInterface;
use App\Facades\CentrifugoFacade;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;
use Tests\TestCase;

class CancelTaskTest extends TestCase
{
    private User $user;
    private Task $task;
    private Courier $courier;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Cancel Task User',
            'email' => 'cancel-task@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->courier = Courier::create([
            'name' => 'Courier Test',
            'uuid' => (string) Str::uuid(),
            'status' => CourierStatusEnum::OT->value,
        ]);

        $this->task = new Task();
        $this->task->uuid = (string) Str::uuid();
        $this->task->status = TaskStatusEnum::CREATED->value;
        $this->task->courier_id = $this->courier->id;
        $this->task->save();

        $customer = Customer::create([
            'name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'uuid' => (string) Str::uuid(),
        ]);

        $pointA = Point::create(['address' => 'Point A', 'lat' => 52.22, 'long' => 21.01]);
        $pointB = Point::create(['address' => 'Point B', 'lat' => 52.23, 'long' => 21.02]);

        $this->order = new Order();
        $this->order->customer_id = $customer->id;
        $this->order->unit_type = 'Medium';
        $this->order->uuid = (string) Str::uuid();
        $this->order->status = OrderStatusEnum::ASSIGNED->value;
        $this->order->task_id = $this->task->id;
        $this->order->start_point_id = $pointA->id;
        $this->order->end_point_id = $pointB->id;
        $this->order->date = '2026-05-31';
        $this->order->save();

        $route = new Route();
        $route->task_id = $this->task->id;
        $route->point_id = $pointA->id;
        $route->sequence = 1;
        $route->point_type = 'START';
        $route->save();
    }

    public function test_guest_cannot_cancel_task(): void
    {
        $response = $this->postJson('/api/task/cancel', [
            'taskUuid' => $this->task->uuid,
        ]);

        $response->assertUnauthorized();
    }

    public function test_cancel_task_when_workflow_running(): void
    {
        $taskWorkflowMock = Mockery::mock(TaskWorkflowInterface::class);
        $taskWorkflowMock->shouldReceive('cancel')
            ->once()
            ->andReturnNull();

        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock) use ($taskWorkflowMock): void {
            $mock->shouldReceive('newRunningWorkflowStub')
                ->once()
                ->with(TaskWorkflowInterface::class, 'task:'.$this->task->uuid)
                ->andReturn($taskWorkflowMock);
        });

        $response = $this->actingAs($this->user)->postJson('/api/task/cancel', [
            'taskUuid' => $this->task->uuid,
        ]);

        $response->assertOk();
        $response->assertJson(['data' => true]);

        // DB state should NOT be touched yet because workflow is running and handles cancellation
        $this->order->refresh();
        $this->task->refresh();
        $this->courier->refresh();

        $this->assertEquals(OrderStatusEnum::ASSIGNED->value, $this->order->status);
        $this->assertEquals($this->task->id, $this->order->task_id);
        $this->assertEquals(TaskStatusEnum::CREATED->value, $this->task->status);
        $this->assertEquals(CourierStatusEnum::OT->value, $this->courier->status);
    }

    public function test_cancel_task_when_workflow_not_running_manual_cleanup(): void
    {
        // Mock the task workflow throwing WorkflowNotFoundException
        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('newRunningWorkflowStub')
                ->with(TaskWorkflowInterface::class, 'task:'.$this->task->uuid)
                ->andThrow(new WorkflowNotFoundException(
                    new \Temporal\DataConverter\EncodedValues(),
                    null
                ));

            // Mock order workflow being found and signaled
            $orderWorkflowMock = Mockery::mock(OrderWorkflowInterface::class);
            $orderWorkflowMock->shouldReceive('unassignFromTask')
                ->once()
                ->with($this->task->uuid)
                ->andReturnNull();

            $mock->shouldReceive('newRunningWorkflowStub')
                ->with(OrderWorkflowInterface::class, 'order:'.$this->order->uuid)
                ->andReturn($orderWorkflowMock);
        });

        CentrifugoFacade::shouldReceive('publish')
            ->once()
            ->with('courier_status', ['uuid' => $this->courier->uuid, 'status' => CourierStatusEnum::RD->value])
            ->andReturnNull();

        CentrifugoFacade::shouldReceive('publish')
            ->once()
            ->with('task_status', ['uuid' => $this->task->uuid, 'status' => TaskStatusEnum::CANCELED->value])
            ->andReturnNull();

        $response = $this->actingAs($this->user)->postJson('/api/task/cancel', [
            'taskUuid' => $this->task->uuid,
        ]);

        $response->assertOk();
        $response->assertJson(['data' => true]);

        $this->order->refresh();
        $this->task->refresh();
        $this->courier->refresh();

        // The task should be canceled, courier status changed to RD and route deleted
        $this->assertEquals(TaskStatusEnum::CANCELED->value, $this->task->status);
        $this->assertNull($this->task->courier_id);
        $this->assertEquals(CourierStatusEnum::RD->value, $this->courier->status);

        $this->assertDatabaseMissing('routes', [
            'task_id' => $this->task->id,
        ]);
    }

    public function test_cancel_task_when_workflow_and_order_workflows_not_running_manual_cleanup(): void
    {
        // Mock the task workflow throwing WorkflowNotFoundException
        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('newRunningWorkflowStub')
                ->with(TaskWorkflowInterface::class, 'task:'.$this->task->uuid)
                ->andThrow(new WorkflowNotFoundException(
                    new \Temporal\DataConverter\EncodedValues(),
                    null
                ));

            // Mock order workflow throwing WorkflowNotFoundException
            $mock->shouldReceive('newRunningWorkflowStub')
                ->with(OrderWorkflowInterface::class, 'order:'.$this->order->uuid)
                ->andThrow(new WorkflowNotFoundException(
                    new \Temporal\DataConverter\EncodedValues(),
                    null
                ));
        });

        CentrifugoFacade::shouldReceive('publish')
            ->once()
            ->with('courier_status', ['uuid' => $this->courier->uuid, 'status' => CourierStatusEnum::RD->value])
            ->andReturnNull();

        CentrifugoFacade::shouldReceive('publish')
            ->once()
            ->with('task_status', ['uuid' => $this->task->uuid, 'status' => TaskStatusEnum::CANCELED->value])
            ->andReturnNull();

        $response = $this->actingAs($this->user)->postJson('/api/task/cancel', [
            'taskUuid' => $this->task->uuid,
        ]);

        $response->assertOk();
        $response->assertJson(['data' => true]);

        // DB state should be updated manually:
        $this->order->refresh();
        $this->task->refresh();
        $this->courier->refresh();

        // The task should be canceled, courier status changed to RD and route deleted
        $this->assertEquals(TaskStatusEnum::CANCELED->value, $this->task->status);
        $this->assertNull($this->task->courier_id);
        $this->assertEquals(CourierStatusEnum::RD->value, $this->courier->status);

        // The order should be unassigned and status set to ACCEPTED
        $this->assertEquals(OrderStatusEnum::ACCEPTED->value, $this->order->status);
        $this->assertNull($this->order->task_id);

        $this->assertDatabaseMissing('routes', [
            'task_id' => $this->task->id,
        ]);
    }
}
