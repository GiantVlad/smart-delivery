<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EditRouteRequest;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Models\Task;
use App\Temporal\UpdateRouteWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;

class RouteController extends Controller
{
    public function __construct(
        private WorkflowClientInterface $workflowClient
    ) {}
    public function getRoute(string $taskUuid): JsonResponse|JsonResource
    {
        try {
            // Verify task workflow exists in Temporal
            $this->workflowClient->newRunningWorkflowStub(
                \App\Temporal\TaskWorkflowInterface::class,
                'task:' . $taskUuid
            )->getState();
        } catch (WorkflowNotFoundException) {
            return response()->json([
                'message' => 'Task workflow not found.',
            ], 404);
        }

        $task = Task::where('uuid', $taskUuid)->firstOrFail();

        $routes = Route::where('task_id', $task->id)->with('point')->orderBy('sequence')->get();

        return RouteResource::collection($routes);
    }

    public function updateRoute(EditRouteRequest $request): JsonResponse
    {
        $workflow = $this->workflowClient->newWorkflowStub(
            UpdateRouteWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $this->workflowClient->start($workflow, $request->get('taskUuid'), $request->get('points'));

        return response()->json('updated');
    }
}
