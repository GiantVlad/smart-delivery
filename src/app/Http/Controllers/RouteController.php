<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EditRouteRequest;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

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
        $task = Task::where('uuid', $request->get('taskUuid'))->first();
        $points = $request->get('points');
        /** @var Collection $routes */
        $routes = $task->routes;
        foreach ($points as $idx => $pointId) {
            if ($routes->isEmpty()) {
                $route = new Route();
                $route->task_id = $task->id;
                $route->sequence = $idx;
                $route->point_id = $pointId;
                $route->save();
            } else {
                $routes->each(static function (Route $route) use ($idx, $pointId) {
                    if ($route->point_id === $pointId) {
                        $route->sequence = $idx;
                        $route->save();
                    }
                });
            }
        }

        return response()->json('updated');
    }
}
