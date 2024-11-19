<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Route;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Temporal\Activity;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\IllegalStateException;

class CreateRoteActivity implements CreateRouteActivityInterface
{
    public function createRoute(string $taskUuid): true
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();
        $orders = $task->orders()->get();

        $from = new Collection();
        $destinations = new Collection();
        foreach ($orders as $order) {
            $from->add($order->start_point_id);
            $destinations->add($order->end_point_id);
        }
        $from = $from->unique();
        $destinations = $destinations->unique();
        $pointIds = $from->concat($destinations)->unique();

        DB::transaction(static function () use ($task, $pointIds) {
            foreach ($pointIds as $idx => $pointId) {
                $route = new Route();
                $route->sequence = $idx;
                $route->task_id = $task->id;
                $route->point_id = $pointId;
                $route->save();
            }
        });

        return true;
    }
}
