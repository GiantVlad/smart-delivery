<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\OrderStatus;
use App\Dto\TaskDto;
use App\Enums\OrderStatusEnum;
use App\Http\Requests\UpdateStatusByCourierRequest;
use App\Models\Order;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use App\Temporal\TaskFinishedActivityInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;

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

    public function updateStatusByCourier(UpdateStatusByCourierRequest $request, OrderStatus $orderStatusDomain): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        $status = $request->get('status');

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            $this->getWfId(),
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
                TaskFinishedActivityInterface::class,
                WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minutes(2))
            );

            $taskDto = new TaskDto(
                $task->courier->uuid,
                [],
                '',
                $task->uuid,
            );

            $this->workflowClient->start($workflow, $taskDto);
        }

        return response()->json(OrderStatusEnum::ASSIGNED->value);
    }
}
