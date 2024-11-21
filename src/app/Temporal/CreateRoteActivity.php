<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\RoutePointTypeEnum;
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

        $tmpObject = new class(){
            public $id;
            public $type;
        };

        foreach ($orders as $order) {
            $tmpObject->id = $order->start_point_id;
            $tmpObject->type = RoutePointTypeEnum::START->value;
            $from->add($tmpObject);
            $tmpObject->id = $order->end_point_id;
            $tmpObject->type = RoutePointTypeEnum::FINISH->value;
            $destinations->add($order->end_point_id);
        }
        $from = $from->unique();
        $destinations = $destinations->unique();

        $destinations->map(function ($destination) use ($from) {
            $matchingItem = $from->first(static fn ($el) => $el->id === $destination->id, false);
            if ($matchingItem) {
                $destination->type = RoutePointTypeEnum::INTERMEDIATE->value;
            }

            return $destination;
        });

        $points = $from->concat($destinations)->unique();

        DB::transaction(static function () use ($task, $points) {
            foreach ($points as $idx => $point) {
                $route = new Route();
                $route->sequence = $idx;
                $route->task_id = $task->id;
                $route->point_id = $point->id;
                $route->point_type = $point->type;
                $route->save();
            }
        });

        return true;
    }
}
