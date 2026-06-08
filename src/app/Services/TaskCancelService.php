<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\Order;
use App\Models\Route;
use App\Models\Courier;
use App\Enums\OrderStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\CourierStatusEnum;
use App\Temporal\TaskWorkflowInterface;
use App\Temporal\OrderWorkflowInterface;
use App\Facades\CentrifugoFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;

class TaskCancelService
{
    public function __construct(
        private WorkflowClientInterface $workflowClient
    ) {}

    /**
     * Cancel a task. Tries to cancel the running Temporal workflow,
     * and falls back to manual database/order workflow cleanup if the workflow is not running.
     *
     * @param Task $task
     * @return void
     */
    public function cancel(Task $task): void
    {
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            TaskWorkflowInterface::class,
            'task:'.$task->uuid,
        );

        try {
            $workflow->cancel();
        } catch (WorkflowNotFoundException) {
            $ordersToCleanupManually = [];
            $orders = Order::where('task_id', $task->id)->get();

            foreach ($orders as $order) {
                try {
                    $orderWorkflow = $this->workflowClient->newRunningWorkflowStub(
                        OrderWorkflowInterface::class,
                        'order:'.$order->uuid,
                    );
                    $orderWorkflow->unassignFromTask($task->uuid);
                } catch (WorkflowNotFoundException) {
                    $ordersToCleanupManually[] = $order;
                }
            }

            DB::transaction(function () use ($task, $ordersToCleanupManually) {
                foreach ($ordersToCleanupManually as $order) {
                    if (!in_array($order->status, [OrderStatusEnum::DELIVERED->value, OrderStatusEnum::CANCELED->value], true)) {
                        $order->status = OrderStatusEnum::ACCEPTED->value;
                    }
                    $order->task_id = null;
                    $order->save();
                }

                // Delete associated routes
                Route::where('task_id', $task->id)->delete();

                // Cancel the task and update courier
                $task->status = TaskStatusEnum::CANCELED->value;

                $courierUuid = null;
                if ($task->courier_id) {
                    $courier = Courier::find($task->courier_id);
                    if ($courier) {
                        $courier->status = CourierStatusEnum::RD->value;
                        $courier->save();
                        $courierUuid = $courier->uuid;
                    }
                }

                $task->courier_id = null;
                $task->save();

                // Broadcast status changes
                try {
                    if ($courierUuid) {
                        CentrifugoFacade::publish('courier_status', [
                            'uuid' => $courierUuid,
                            'status' => CourierStatusEnum::RD->value,
                        ]);
                    }
                    CentrifugoFacade::publish('task_status', [
                        'uuid' => $task->uuid,
                        'status' => TaskStatusEnum::CANCELED->value,
                    ]);
                } catch (\Throwable $e) {
                    Log::error($e);
                }
            });
        }
    }
}
