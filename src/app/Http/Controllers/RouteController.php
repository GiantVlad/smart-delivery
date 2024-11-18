<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteController extends Controller
{
    public function getRoute(string $taskUuid): JsonResource
    {
        $task = Task::where('uuid', $taskUuid)->with('routes')->firstOrFail();

        return RouteResource::collection($task->routes);
    }

    public function updateRoute(Request $request): JsonResponse
    {
        $task = Task::where('uuid', $request->get('taskUuid'))->firstOrFail();
        $points = $request->get('points');
        $routes = $task->routes;
        if ($routes->isEmpty()) {
            foreach ($points as $idx => $pointId) {
                $route = new Route();
                $route->task_id = $task->id;
                $route->sequence = $idx;
                $route->point_id = $pointId;
                $route->save();
            }
        }

        return response()->json('updated');
    }
}
