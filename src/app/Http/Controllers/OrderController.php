<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Http\Requests\UnassignOrderRequest;
use App\Models\Order;
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
}
