<?php

declare(strict_types=1);

namespace Tests\Integration\Temporal;

use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\TaskOrderProjectionActivity;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskOrderProjectionActivityTest extends TestCase
{
    public function test_attach_connects_order_to_task_projection(): void
    {
        $courier = Courier::create([
            'name' => 'Projection Courier',
            'status' => CourierStatusEnum::RD->value,
            'uuid' => (string) Str::uuid(),
        ]);
        $customer = Customer::create([
            'name' => 'Projection',
            'last_name' => 'Customer',
            'email' => 'projection-customer@example.com',
            'uuid' => (string) Str::uuid(),
        ]);
        $task = new Task();
        $task->courier_id = $courier->id;
        $task->uuid = (string) Str::uuid();
        $task->save();

        $order = new Order();
        $order->customer_id = $customer->id;
        $order->unit_type = 'Medium';
        $order->uuid = (string) Str::uuid();
        $order->status = OrderStatusEnum::ACCEPTED->value;
        $order->task_id = null;
        $order->save();

        $activity = new TaskOrderProjectionActivity();
        $activity->attach($task->uuid, $order->uuid);

        $this->assertDatabaseHas('orders', [
            'uuid' => $order->uuid,
            'status' => OrderStatusEnum::ASSIGNED->value,
            'task_id' => $task->id,
        ]);
    }
}
