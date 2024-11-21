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

class RemoveFromRouteActivity implements RemoveFromRouteActivityInterface
{
    public function removeFromRoute(
        string $taskUuid,
        int $startPointId,
        int $endPointId,
    ): array {
        $task = Task::where('uuid', $taskUuid)->with(['orders', 'routes'])->first();

        /** @var Collection $routes */
        $routes = $task->routes;

        /** @var Collection $orders */
        $orders = $task->orders;

        foreach ([$startPointId, $endPointId] as $idx => $pointId) {
            $foundStart = $orders->firstWhere('start_point_id', $pointId);
            $foundEnd = $orders->firstWhere('end_point_id', $pointId);

            if (!$foundStart && !$foundEnd) {
                /** @var Route|null $route */
                $route = $routes->firstWhere('point_id', $pointId);
                $route->delete();
            } else {
                $route = $routes->firstWhere('point_id', $pointId);
                if ($route->point_type === RoutePointTypeEnum::INTERMEDIATE->value
                    && ($foundStart->count() === 0 || $foundEnd->count() === 0)
                ) {
                    $route->point_type = $idx === 0
                        ? RoutePointTypeEnum::FINISH->value
                        : RoutePointTypeEnum::START->value;
                }
            }

            if ($foundStart->count() === 1 && $foundEnd->count() === 1) {
                $route = $routes->firstWhere('point_id', $pointId);

                $route->delete();
            }
        }

        return [$startPointId, $endPointId];
    }
}
