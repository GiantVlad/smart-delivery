<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Route;
use App\Models\Task;
use Illuminate\Support\Collection;
use Temporal\Activity;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\IllegalStateException;

class UpdateRoteActivity implements UpdateRouteActivityInterface
{
    public function updateRoute(string $taskUuid, array $points): array
    {
        $task = Task::where('uuid', $taskUuid)->first();

        /** @var Collection $routes */
        $routes = $task->routes;
        foreach ($points as $idx => $pointId) {
            $routes->each(static function (Route $route) use ($idx, $pointId) {
                if ($route->point_id === $pointId) {
                    $route->sequence = $idx;
                    $route->save();
                }
            });
        }

        return $points;
    }
}
