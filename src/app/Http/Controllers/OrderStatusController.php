<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\OrderConfirmationRequest;
use App\Http\Requests\UpdateStatusByCourierRequest;
use App\Models\Order;
use App\Temporal\OrderWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Temporal\Exception\Client\WorkflowNotFoundException;

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

        try {
            $workflow->confirm($status);
        } catch (WorkflowNotFoundException $exception) {
            return $this->workflowNotFoundResponse($orderUuid, $exception);
        }

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

        try {
            $workflow->updateStatus($status);
        } catch (WorkflowNotFoundException $exception) {
            return $this->workflowNotFoundResponse($orderUuid, $exception);
        }

        return response()->json($status);
    }

    private function workflowNotFoundResponse(string $orderUuid, WorkflowNotFoundException $exception): JsonResponse
    {
        Log::warning('Order workflow is not running', [
            'orderUuid' => $orderUuid,
            'workflowId' => 'order:'.$orderUuid,
            'exception' => $exception->getMessage(),
        ]);

        return response()->json([
            'message' => 'Order workflow is not running. Recreate the order or reset its Temporal workflow.',
            'orderUuid' => $orderUuid,
        ], 409);
    }
}
