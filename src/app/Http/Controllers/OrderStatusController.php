<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Order;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function confirmOrder(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            $this->getWfId(),
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ACCEPTED->value);

        return response()->json('Updated');
    }

    public function assignCourier(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        $courierUuid = $request->get('courierUuid');
        $order = Order::where('uuid', $orderUuid)->firstOrFail();

        if (! in_array($order->status, [OrderStatusEnum::ACCEPTED->value])) {
            throw new \Exception("You can't assign order that has status $order->status", 422);
        }

        Courier::where('uuid', $courierUuid)->firstOrFail();

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            $this->getWfId(),
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value, $courierUuid);

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }

    public function updateStatusByCourier(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        $status = $request->get('status');
        $order = Order::where('uuid', $orderUuid)->firstOrFail();

        try {
            $statusEnum = OrderStatusEnum::from($status);
        } catch (\ValueError $error) {
            throw new \Exception("Invalid status: $status", 422);
        }

        if (! in_array($order->status, OrderStatusEnum::courierCanUpdate())) {
            throw new \Exception("You can't change order status to $status since current status is $order->status", 422);
        }

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            $this->getWfId(),
        );

        $workflow->updateStatus($orderUuid, $status);

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }
}
