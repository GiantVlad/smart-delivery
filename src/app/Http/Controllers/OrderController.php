<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Http\Requests\AddOrderRequest;
use App\Http\Requests\UnassignOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderToAssignResource;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\TaskWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderController extends Controller
{
    public function unassignOrder(UnassignOrderRequest $request): JsonResponse
    {
        $order = Order::where('uuid', $request->get('orderUuid'))
            ->with('task', 'task.courier', 'customer')
            ->first();

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            TaskWorkflowInterface::class,
            'task:'.$order->task->uuid,
        );

        $workflow->removeOrder($order->uuid);

        return response()->json(['data' => true]);
    }

    public function addOrdersToTask(AddOrderRequest $request): JsonResponse
    {
        $task = Task::where('uuid', $request->get('taskUuid'))->first();

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            TaskWorkflowInterface::class,
            'task:'.$task->uuid,
        );

        $workflow->addOrders($request->get('orderUuids'));

        return response()->json(['data' => true]);
    }

    public function getOrdersToAssign(): JsonResource
    {
        $orders = Order::with('startPoint', 'endPoint')
            ->whereNull('task_id')
            ->whereIn('status', [OrderStatusEnum::ACCEPTED->value])
            ->orderBy('updated_at', 'desc')
            ->get();

        return OrderToAssignResource::collection($orders);
    }

    public function getOrders(): JsonResource
    {
        $orders = Order::with('customer', 'task.courier', 'startPoint', 'endPoint')
            ->limit(30)
            ->orderBy('updated_at', 'desc')
            ->get();

        return OrderResource::collection($orders);
    }
}
