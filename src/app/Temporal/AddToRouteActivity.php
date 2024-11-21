<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\RoutePointTypeEnum;
use App\Models\Route;
use App\Models\Task;
use Illuminate\Support\Collection;
use Temporal\Activity;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\IllegalStateException;

class AddToRouteActivity implements AddToRouteActivityInterface
{
    public function addToRoute(
        string $taskUuid,
        int $startPointId,
        int $endPointId,
    ): array {
        $task = Task::where('uuid', $taskUuid)->first();

        /** @var Collection $routes */
        $routes = $task->routes;

        $lastStart = 0;
        foreach ($routes as $route) {
            if ($startPointId && $route->point_id === $startPointId && $route->point_type !== RoutePointTypeEnum::FINISH->value) {
                $startPointId = null;
                continue;
            }
            if ($endPointId && $route->point_id === $endPointId && $route->point_type !== RoutePointTypeEnum::START->value) {
                $endPointId = null;
                continue;
            }

            if ($route->point_type !== RoutePointTypeEnum::FINISH->value) {
                $lastStart = $route->sequence;
            }
            if ($route->point_type !== RoutePointTypeEnum::START->value) {
                $lastFinish = $route->sequence;
            }
        }

        if ($startPointId !== null) {
            $route = new Route();
            $route->point_type = RoutePointTypeEnum::START->value;
            $route->point_id = $startPointId;
            $route->sequince = $lastStart;
            $route->save();
        }

        if ($endPointId !== null) {
            $route = new Route();
            $route->point_type = RoutePointTypeEnum::FINISH->value;
            $route->point_id = $endPointId;
            $route->sequince = $lastFinish;
            $route->save();
        }

        $routes->map(static function (Route $route) use ($lastStart, $lastFinish, $startPointId, $endPointId) {
            if ($startPointId !== null && $startPointId !== $route->point_id && $route->sequince >= $lastStart) {
                $route->sequince++;
            }
            if ($endPointId !== null && $endPointId !== $route->point_id && $route->sequince >= $lastFinish) {
                $route->sequince++;
            }
        });

        return [$startPointId, $endPointIdId];
    }
}
