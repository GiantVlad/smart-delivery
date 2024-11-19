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

class RouteController extends Controller
{
    public function getRoute(string $taskUuid): JsonResource
    {
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
