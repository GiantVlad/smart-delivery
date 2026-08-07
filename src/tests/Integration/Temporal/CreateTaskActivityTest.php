<?php

declare(strict_types=1);

namespace Tests\Integration\Temporal;

use App\Dto\TaskDto;
use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Temporal\CreateTaskActivity;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateTaskActivityTest extends TestCase
{
    public function test_create_task_activity_does_not_assign_orders_before_workflow_signal(): void
    {
        $courier = Courier::create([
            'name' => 'Task Courier',
            'status' => CourierStatusEnum::RD->value,
            'uuid' => (string) Str::uuid(),
        ]);
        $customer = Customer::create([
            'name' => 'Task',
            'last_name' => 'Customer',
            'email' => 'task-customer@example.com',
            'uuid' => (string) Str::uuid(),
        ]);
        $order = new Order();
        $order->customer_id = $customer->id;
        $order->unit_type = 'Medium';
        $order->uuid = (string) Str::uuid();
        $order->status = OrderStatusEnum::ACCEPTED->value;
        $order->task_id = null;
        $order->save();

        $taskUuid = (string) Str::uuid();

        $activity = new CreateTaskActivity();
        $activity->createTask(new TaskDto($courier->uuid, [$order->uuid], $taskUuid));

        $this->assertDatabaseHas('tasks', [
            'uuid' => $taskUuid,
            'status' => TaskStatusEnum::CREATED->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'uuid' => $order->uuid,
            'status' => OrderStatusEnum::ACCEPTED->value,
            'task_id' => null,
        ]);
    }
}
