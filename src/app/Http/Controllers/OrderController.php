<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\AssignOrderDto;
use App\Enums\OrderStatusEnum;
use App\Http\Requests\AddOrderRequest;
use App\Http\Requests\UnassignOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderToAssignResource;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\AssignOrderWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Temporal\Client\WorkflowOptions;

class OrderController extends Controller
{
    public function unassignOrder(UnassignOrderRequest $request): JsonResponse
    {
        $order = Order::where('uuid', $request->get('orderUuid'))->first();
        $order->task_id = null;
        $order->status = OrderStatusEnum::ACCEPTED->value;
        $order->save();

        return response()->json(['data' => true]);
    }

    public function addOrdersToTask(AddOrderRequest $request): JsonResponse
    {
        $task = Task::whare('uuid', $request->get('taskUuid'))->first();

        $dto = new AssignOrderDto(
            [],
            $task->uuid,
            $task->courier->uuid
        );

        $orders = Order::whereIn('uuid', $request->get('orderUuids'))->with('customer')->get();
        foreach ($orders as $order) {
            $dto->orderCustomerUuids[$order->uuid] = $order->customer->uuid;
        }

        $workflow = $this->workflowClient->newWorkflowStub(
            AssignOrderWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minutes(3))
        );

        $this->workflowClient->start($workflow, $dto);

        return response()->json(['data' => true]);
    }

    public function getOrdersToAssign(): JsonResource
    {
        $orders = Order::with('startPoint', 'endPoint')
            ->whereNull('task_id')
            ->whereIn('status', [OrderStatusEnum::ACCEPTED->value, OrderStatusEnum::CANCELED->value])
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
