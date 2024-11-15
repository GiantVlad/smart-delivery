<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Courier;
use App\Models\Order;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowClientInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

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

        if (! in_array($order->status,  [OrderStatusEnum::ACCEPTED->value])) {
            throw new \Exception("You can't assign order that has status $order->status", 412);
        }

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
