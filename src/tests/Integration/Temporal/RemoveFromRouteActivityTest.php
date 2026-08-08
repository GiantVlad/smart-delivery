<?php

declare(strict_types=1);

namespace Tests\Integration\Temporal;

use App\Dto\OrderWorkflowState;
use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\RoutePointTypeEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\CreateRoteActivity;
use App\Temporal\OrderWorkflowInterface;
use App\Temporal\RemoveFromRouteActivity;
use Illuminate\Support\Str;
use Mockery;
use Temporal\Client\WorkflowClientInterface;
use Tests\TestCase;

class RemoveFromRouteActivityTest extends TestCase
{
    public function test_it_removes_unique_points_and_keeps_a_shared_delivery_point(): void
    {
        [$task, $removedOrder, $remainingOrder] = $this->createTaskWithOrders(1, 2, 3, 2);
        $this->createRoute($task, $removedOrder, $remainingOrder);

        $activity = new RemoveFromRouteActivity($this->workflowClientFor($remainingOrder));
        $activity->removeFromRoute(
            $task->uuid,
            [$remainingOrder->uuid],
            $removedOrder->start_point_id,
            $removedOrder->end_point_id,
        );

        $this->assertDatabaseMissing('routes', [
            'task_id' => $task->id,
            'point_id' => 1,
        ]);
        $this->assertDatabaseHas('routes', [
            'task_id' => $task->id,
            'point_id' => 2,
            'point_type' => RoutePointTypeEnum::FINISH->value,
        ]);
        $this->assertDatabaseHas('routes', [
            'task_id' => $task->id,
            'point_id' => 3,
            'point_type' => RoutePointTypeEnum::START->value,
        ]);
    }

    public function test_it_changes_an_intermediate_point_to_pickup_when_only_pickups_remain(): void
    {
        [$task, $removedOrder, $remainingOrder] = $this->createTaskWithOrders(1, 2, 2, 3);
        $this->createRoute($task, $removedOrder, $remainingOrder);

        $activity = new RemoveFromRouteActivity($this->workflowClientFor($remainingOrder));
        $activity->removeFromRoute(
            $task->uuid,
            [$remainingOrder->uuid],
            $removedOrder->start_point_id,
            $removedOrder->end_point_id,
        );

        $this->assertDatabaseMissing('routes', [
            'task_id' => $task->id,
            'point_id' => 1,
        ]);
        $this->assertDatabaseHas('routes', [
            'task_id' => $task->id,
            'point_id' => 2,
            'point_type' => RoutePointTypeEnum::START->value,
        ]);
        $this->assertDatabaseHas('routes', [
            'task_id' => $task->id,
            'point_id' => 3,
            'point_type' => RoutePointTypeEnum::FINISH->value,
        ]);
    }

    private function createTaskWithOrders(
        int $removedStartPointId,
        int $removedEndPointId,
        int $remainingStartPointId,
        int $remainingEndPointId,
    ): array {
        $courier = Courier::create([
            'name' => 'Route Courier',
            'status' => CourierStatusEnum::RD->value,
            'uuid' => (string) Str::uuid(),
        ]);
        $customer = Customer::create([
            'name' => 'Route',
            'last_name' => 'Customer',
            'email' => 'route-customer-'.Str::uuid().'@example.com',
            'uuid' => (string) Str::uuid(),
        ]);
        $task = new Task();
        $task->courier_id = $courier->id;
        $task->uuid = (string) Str::uuid();
        $task->save();

        $removedOrder = $this->createOrder(
            $customer,
            $task,
            $removedStartPointId,
            $removedEndPointId,
        );
        $remainingOrder = $this->createOrder(
            $customer,
            $task,
            $remainingStartPointId,
            $remainingEndPointId,
        );

        return [$task, $removedOrder, $remainingOrder];
    }

    private function createOrder(Customer $customer, Task $task, int $startPointId, int $endPointId): Order
    {
        $order = new Order();
        $order->customer_id = $customer->id;
        $order->task_id = $task->id;
        $order->unit_type = 'Medium';
        $order->uuid = (string) Str::uuid();
        $order->status = OrderStatusEnum::ASSIGNED->value;
        $order->start_point_id = $startPointId;
        $order->end_point_id = $endPointId;
        $order->save();

        return $order;
    }

    private function createRoute(Task $task, Order ...$orders): void
    {
        (new CreateRoteActivity())->createRoute(
            $task->uuid,
            array_map(static fn (Order $order): string => $order->uuid, $orders),
        );
    }

    private function workflowClientFor(Order $order): WorkflowClientInterface
    {
        $orderWorkflow = Mockery::mock(OrderWorkflowInterface::class);
        $orderWorkflow->shouldReceive('getState')->once()->andReturn(new OrderWorkflowState(
            orderUuid: $order->uuid,
            customerUuid: (string) $order->customer->uuid,
            status: $order->status,
            taskUuid: (string) $order->task->uuid,
            unitType: $order->unit_type,
            startPointId: $order->start_point_id,
            endPointId: $order->end_point_id,
            timeRanges: [],
        ));

        $workflowClient = Mockery::mock(WorkflowClientInterface::class);
        $workflowClient->shouldReceive('newRunningWorkflowStub')
            ->once()
            ->with(OrderWorkflowInterface::class, 'order:'.$order->uuid)
            ->andReturn($orderWorkflow);

        return $workflowClient;
    }
}
