<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\CourierManager;
use App\Dto\CreateTaskCommand;
use App\Enums\OrderStatusEnum;
use App\Http\Requests\CancelTaskRequest;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Resources\TaskCreateFormResource;
use App\Http\Resources\TaskResource;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\TaskWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\Client\WorkflowNotFoundException;

class TaskController extends Controller
{
    public function createTaskForm(string $date, CourierManager $courierManager)
    {
        $date = Carbon::parse($date);
        $dto = new class
        {
            public Collection $orders;

            public Collection $couriers;
        };

        $dto->orders = Order::where('status', OrderStatusEnum::ACCEPTED->value)
            ->whereDate('date', $date)
            ->with(['startPoint', 'endPoint'])
            ->orderBy('updated_at')
            ->get();

        $dto->couriers = $courierManager->getFreeCouriersForDay($date);

        return TaskCreateFormResource::make($dto);
    }

    public function getTasks(): JsonResource
    {
        $tasks = Task::with('courier')
            ->limit(30)
            ->orderBy('updated_at', 'desc')
            ->get();

        return TaskResource::collection($tasks);
    }

    public function createTask(CreateTaskRequest $request): JsonResponse
    {
        $courierUuid = $request->get('courierUuid');
        $orderUuids = $request->get('orderUuids');
        $taskUuid = Str::uuid()->toString();

        $workflow = $this->workflowClient->newWorkflowStub(
            TaskWorkflowInterface::class,
            WorkflowOptions::new()
                ->withWorkflowId('task:'.$taskUuid)
                ->withWorkflowExecutionTimeout(CarbonInterval::days(30))
        );

        $this->workflowClient->start($workflow, new CreateTaskCommand($taskUuid, $courierUuid, $orderUuids));

        return response()->json(['data' => ['uuid' => $taskUuid]]);
    }

    public function cancelTask(CancelTaskRequest $request): JsonResponse
    {
        $taskUuid = $request->get('taskUuid');

        $workflow = $this->workflowClient->newRunningWorkflowStub(
            TaskWorkflowInterface::class,
            'task:'.$taskUuid,
        );

        try {
            $workflow->cancel();
        } catch (WorkflowNotFoundException) {
            return response()->json([
                'message' => 'Task workflow is not running. Recreate the task or reset its Temporal workflow.',
                'taskUuid' => $taskUuid,
            ], 409);
        }

        return response()->json(['data' => true]);
    }
}
