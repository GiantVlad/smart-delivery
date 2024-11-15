<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\CreateOrderDto;
use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Task;
use App\Temporal\CreateOrderWorkflowInterface;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;
use Carbon\CarbonInterval;

class TaskController extends Controller
{
    public function createTaskForm()
    {
        $orders = Order::where('status', OrderStatusEnum::ACCEPTED->value)->orderBy('id', 'desc')->get();

        $couriers = Courier::where('status', CourierStatusEnum::RD->value)->get();

        return view('new-task', ['orders' => $orders, 'couriers' => $couriers]);
    }

    public function getTasks()
    {
        $tasks = Task::with('courier')
            ->limit(30)
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('tasks', ['tasks' => $tasks]);
    }

    public function createTask(Request $request)
    {
        $courierUuid = $request->get('courierUuid');
        $ordersIds = $request->get('ordersIds');

//        $workflow = $this->workflowClient->newWorkflowStub(
//            CreateOrderWorkflowInterface::class,
//            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
//        );

        $customer = Courier::where('uuid', $courierUuid)->firstOrFail();
        $orders = Order::whereIn('id', $ordersIds)->get();
        $task = new Task();
        foreach ($orders as $order) {
            $order->task()->associate($task);
            $order->save();
        }


       //  $this->workflowClient->start($workflow, $orderDTO);

        return redirect('/tasks');
    }
}
