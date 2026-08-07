<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateRouteTest extends TestCase
{
    private Point $startPoint;

    private Point $endPoint;

    private Customer $customer;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'update-route-test@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->startPoint = Point::create(['address' => 'Start', 'lat' => 52.22, 'long' => 21.01]);
        $this->endPoint = Point::create(['address' => 'End', 'lat' => 52.23, 'long' => 21.02]);
        $this->customer = Customer::create([
            'name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'update-route-customer@example.com',
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createTaskWithOrder(string $status): Task
    {
        $courier = Courier::create([
            'name' => 'Courier Test',
            'uuid' => (string) Str::uuid(),
        ]);

        $task = new Task;
        $task->uuid = (string) Str::uuid();
        $task->status = $status;
        $task->courier_id = $courier->id;
        $task->save();

        $order = new Order;
        $order->customer_id = $this->customer->id;
        $order->unit_type = 'Medium';
        $order->uuid = (string) Str::uuid();
        $order->status = 'assigned';
        $order->task_id = $task->id;
        $order->start_point_id = $this->startPoint->id;
        $order->end_point_id = $this->endPoint->id;
        $order->date = '2026-06-10';
        $order->save();

        return $task;
    }

    public function test_cannot_update_route_for_finished_task(): void
    {
        $task = $this->createTaskWithOrder(TaskStatusEnum::FINISHED->value);

        $response = $this->actingAs($this->user)->postJson('/api/update-route', [
            'taskUuid' => $task->uuid,
            'points' => [$this->startPoint->id, $this->endPoint->id],
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_update_route_for_canceled_task(): void
    {
        $task = $this->createTaskWithOrder(TaskStatusEnum::CANCELED->value);

        $response = $this->actingAs($this->user)->postJson('/api/update-route', [
            'taskUuid' => $task->uuid,
            'points' => [$this->startPoint->id, $this->endPoint->id],
        ]);

        $response->assertUnprocessable();
    }
}
