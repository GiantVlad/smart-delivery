<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\CreateTaskDto;
use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\CreateTaskWorkflowInterface;
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
        $orderIds = explode(',', $request->get('orderIds') ?? '');

        $workflow = $this->workflowClient->newWorkflowStub(
            CreateTaskWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minutes(2))
        );

       $this->workflowClient->start($workflow, new CreateTaskDto($courierUuid, $orderIds));

        return redirect('/tasks');
    }
}
