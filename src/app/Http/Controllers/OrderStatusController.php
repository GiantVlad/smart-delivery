<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Order;
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
            '5bd134de-8b6c-4cdb-a9d2-e4750a4847cb',
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
            '5bd134de-8b6c-4cdb-a9d2-e4750a4847cb',
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value);

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }
}
