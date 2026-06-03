<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\OrderConfirmationRequest;
use App\Http\Requests\UpdateStatusByCourierRequest;
use App\Models\Order;
use App\Temporal\OrderWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OrderStatusController extends Controller
{
    public function confirmOrder(OrderConfirmationRequest $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();
        $status = $request->get('status');
        Log::info("Order $orderUuid status updated: $status");

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderWorkflowInterface::class,
            'order:'.$orderUuid,
        );

        $workflow->confirm($status);

        return response()->json('Updated');
    }

    public function updateStatusByCourier(UpdateStatusByCourierRequest $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        $status = $request->get('status');

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderWorkflowInterface::class,
            'order:'.$orderUuid,
        );

        $workflow->updateStatus($status);

        return response()->json($status);
    }
}
