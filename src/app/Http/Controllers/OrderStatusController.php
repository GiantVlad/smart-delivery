<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Temporal\CreateOrderWorkflowInterface;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowClientInterface;
use Carbon\CarbonInterval;

class OrderStatusController extends Controller
{
    public function confirmOrder(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            '2510b70f-94db-4132-8665-c4d7432cd858',
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ACCEPTED->value);

        return response()->json('Updated');
    }

    public function assignCourier(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        $courierUuid = $request->get('courierUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();
        Courier::where('uuid', $courierUuid)->firstOrFail();
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            '2510b70f-94db-4132-8665-c4d7432cd858',
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value);

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }
}
