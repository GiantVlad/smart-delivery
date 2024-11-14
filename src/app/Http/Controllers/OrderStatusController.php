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
use Illuminate\Support\Facades\Cache;

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
        Order::where('uuid', $orderUuid)->firstOrFail();
        Courier::where('uuid', $courierUuid)->firstOrFail();
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            $this->getWfId(),
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ASSIGNED->value, $courierUuid);

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getWfId(): string
    {
        if (Cache::has(OrderStatusHandlerWorkflowInterface::WORKFLOW_STATUS_HANDLER_KEY)) {
            return Cache::get(OrderStatusHandlerWorkflowInterface::WORKFLOW_STATUS_HANDLER_KEY);
        }

        throw new \Exception('workflow ID is not defined in cache');
    }
}
