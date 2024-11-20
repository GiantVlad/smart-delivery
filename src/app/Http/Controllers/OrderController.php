<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Http\Requests\AddOrderRequest;
use App\Http\Requests\UnassignOrderRequest;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

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

    public function addOrdersToTask(AddOrderRequest $request)
    {
        $task = Task::where('uuid', $request->get('taskUuid'))->first();
        $orders = Order::whereIn('uuid', $request->get('orderUuids'))->get();
        foreach ($orders as $order) {
            $order->task_id = $task->id;
            $order->status = OrderStatusEnum::ASSIGNED->value;
            $order->save();
        }
    }
}
