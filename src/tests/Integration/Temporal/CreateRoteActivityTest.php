<?php

namespace Tests\Integration\Temporal;

use App\Enums\RoutePointTypeEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Route;
use App\Models\Task;
use App\Temporal\CreateRoteActivity;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateRoteActivityTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testCreateRouteOneOrder(): void
    {
        $task = $this->createTask([[1 => 2]]);
        $createRoteActivity = new CreateRoteActivity();
        $createRoteActivity->createRoute($task->uuid);
        $routes = Route::where('task_id', $task->id)->get();

        $this->assertCount(2, $routes);
        $this->assertEquals(1, $routes[0]->point_id);
        $this->assertEquals(0, $routes[0]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[0]->point_type);

        $this->assertEquals(2, $routes[1]->point_id);
        $this->assertEquals(1, $routes[1]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[1]->point_type);
    }

    public function testCreateRoute2OrdersFromSameAddress(): void
    {
        $task = $this->createTask([[1 => 2], [1 => 3]]);
        $createRoteActivity = new CreateRoteActivity();
        $createRoteActivity->createRoute($task->uuid);
        $routes = Route::where('task_id', $task->id)->get();

        $this->assertCount(3, $routes);
        $this->assertEquals(1, $routes[0]->point_id);
        $this->assertEquals(0, $routes[0]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[0]->point_type);

        $this->assertEquals(3, $routes[1]->point_id);
        $this->assertEquals(1, $routes[1]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[1]->point_type);

        $this->assertEquals(2, $routes[2]->point_id);
        $this->assertEquals(2, $routes[2]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[2]->point_type);
    }

    public function testCreateRoute2OrdersToSameAddress(): void
    {
        $task = $this->createTask([[1 => 3], [2 => 3]]);
        $createRoteActivity = new CreateRoteActivity();
        $createRoteActivity->createRoute($task->uuid);
        $routes = Route::where('task_id', $task->id)->get();

        $this->assertCount(3, $routes);
        $this->assertEquals(1, $routes[0]->point_id);
        $this->assertEquals(0, $routes[0]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[0]->point_type);

        $this->assertEquals(2, $routes[1]->point_id);
        $this->assertEquals(1, $routes[1]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[1]->point_type);

        $this->assertEquals(3, $routes[2]->point_id);
        $this->assertEquals(2, $routes[2]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[2]->point_type);
    }

    public function testCreateRoute2OrdersDiffAddresses(): void
    {
        $task = $this->createTask([[1 => 2], [3 => 4]]);
        $createRoteActivity = new CreateRoteActivity();
        $createRoteActivity->createRoute($task->uuid);
        $routes = Route::where('task_id', $task->id)->get();

        $this->assertCount(4, $routes);
        $this->assertEquals(1, $routes[0]->point_id);
        $this->assertEquals(0, $routes[0]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[0]->point_type);

        $this->assertEquals(3, $routes[1]->point_id);
        $this->assertEquals(1, $routes[1]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[1]->point_type);

        $this->assertEquals(4, $routes[2]->point_id);
        $this->assertEquals(2, $routes[2]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[2]->point_type);

        $this->assertEquals(2, $routes[3]->point_id);
        $this->assertEquals(3, $routes[3]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[3]->point_type);
    }

    public function testCreateRoute2OrdersWithINTERMEDIATE(): void
    {
        $task = $this->createTask([[1 => 2], [2 => 3]]);
        $createRoteActivity = new CreateRoteActivity();
        $createRoteActivity->createRoute($task->uuid);
        $routes = Route::where('task_id', $task->id)->get();

        $this->assertCount(3, $routes);
        $this->assertEquals(1, $routes[0]->point_id);
        $this->assertEquals(0, $routes[0]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[0]->point_type);

        $this->assertEquals(2, $routes[1]->point_id);
        $this->assertEquals(1, $routes[1]->sequence);
        $this->assertEquals(RoutePointTypeEnum::INTERMEDIATE->value, $routes[1]->point_type);

        $this->assertEquals(3, $routes[2]->point_id);
        $this->assertEquals(2, $routes[2]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[2]->point_type);
    }

    public function testCreateRoute5Orders(): void
    {
        $task = $this->createTask([[1 => 2], [4 => 5], [3 => 4], [1 => 5], [2 => 4]]);
        $createRoteActivity = new CreateRoteActivity();
        $createRoteActivity->createRoute($task->uuid);
        $routes = Route::where('task_id', $task->id)->get();

        $this->assertCount(5, $routes);
        $this->assertEquals(1, $routes[0]->point_id);
        $this->assertEquals(0, $routes[0]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[0]->point_type);

        $this->assertEquals(3, $routes[1]->point_id);
        $this->assertEquals(1, $routes[1]->sequence);
        $this->assertEquals(RoutePointTypeEnum::START->value, $routes[1]->point_type);

        $this->assertEquals(2, $routes[2]->point_id);
        $this->assertEquals(2, $routes[2]->sequence);
        $this->assertEquals(RoutePointTypeEnum::INTERMEDIATE->value, $routes[2]->point_type);

        $this->assertEquals(4, $routes[3]->point_id);
        $this->assertEquals(3, $routes[3]->sequence);
        $this->assertEquals(RoutePointTypeEnum::INTERMEDIATE->value, $routes[2]->point_type);

        $this->assertEquals(5, $routes[4]->point_id);
        $this->assertEquals(4, $routes[4]->sequence);
        $this->assertEquals(RoutePointTypeEnum::FINISH->value, $routes[4]->point_type);
    }

    private function createTask(array $points): Task
    {
        $courier = Courier::first();
        $customer = Customer::first();
        $task = new Task();
        $task->courier_id = $courier->id;
        $task->uuid = Str::uuid();
        $task->save();
        foreach ($points as $point) {
            $order = $this->createOrder($customer, array_key_first($point), $point[array_key_first($point)]);
            $order->task_id = $task->id;
            $order->save();
        }

        return $task;
    }

    private function createOrder(Customer $customer, $start, $end): Order
    {
        $order = new Order();
        $order->uuid = Str::uuid();
        $order->unit_type = 'Medium';
        $order->start_point_id = $start;
        $order->end_point_id = $end;
        $order->customer_id = $customer->id;
        $order->save();

        return $order;
    }
}
