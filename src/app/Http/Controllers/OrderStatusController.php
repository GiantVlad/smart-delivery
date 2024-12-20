<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\OrderStatus;
use App\Dto\TaskDto;
use App\Enums\OrderStatusEnum;
use App\Http\Requests\OrderConfirmationRequest;
use App\Http\Requests\UpdateStatusByCourierRequest;
use App\Models\Order;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use App\Temporal\TaskFinishWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Temporal\Client\WorkflowOptions;

class OrderStatusController extends Controller
{
    public function confirmOrder(OrderConfirmationRequest $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();
        $status = $request->get('status');

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            OrderStatusHandlerWorkflowInterface::WORKFLOW_ID,
        );

        $workflow->updateStatus($orderUuid, $status);

        return response()->json('Updated');
    }

    public function updateStatusByCourier(UpdateStatusByCourierRequest $request, OrderStatus $orderStatusDomain): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        $status = $request->get('status');

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            OrderStatusHandlerWorkflowInterface::WORKFLOW_ID,
        );

        $workflow->updateStatus($orderUuid, $status);

        $task = Order::whereUuid($orderUuid)->first()->task;

        if (in_array(
                OrderStatusEnum::tryFrom($status),
                [OrderStatusEnum::DELIVERED, OrderStatusEnum::CANCELED],
                true)
            && $orderStatusDomain->isOneOrderLeft($task)
        ) {
            $workflow = $this->workflowClient->newWorkflowStub(
                TaskFinishWorkflowInterface::class,
                WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minutes(2))
            );

            $taskDto = new TaskDto(
                $task->courier->uuid,
                [],
                $task->uuid,
            );

            $this->workflowClient->start($workflow, $taskDto);
        }

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }
}
