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

        $tmpObjectFn = static function (int $pointId, string $type)  {
            $obj = new class() {
                public $id;
                public $type;
            };
            $obj->id = $pointId;
            $obj->type = $type;

            return $obj;
        };

        foreach ($orders as $order) {
            $from->add($tmpObjectFn($order->start_point_id, RoutePointTypeEnum::START->value));
            $destinations->add($tmpObjectFn($order->end_point_id, RoutePointTypeEnum::FINISH->value));
        }

        $from = $from->unique();
        $destinations = $destinations->unique();

        $from->map(function ($fromItem) use ($destinations) {
            $matchingItem = $destinations->first(static fn ($el) => $el->id === $fromItem->id, false);
            if ($matchingItem) {
                $fromItem->type = RoutePointTypeEnum::INTERMEDIATE->value;
            }

            return $fromItem;
        });

        $destinations->map(function ($destination) use ($from) {
            $matchingItem = $from->first(static fn ($el) => $el->id === $destination->id, false);
            if ($matchingItem) {
                $destination->type = RoutePointTypeEnum::INTERMEDIATE->value;
            }

            return $destination;
        });

        $destinations = $destinations->sort(function ($destination) use ($from) {
            $matchingItem = $from->first(static fn ($el) => $el->id === $destination->id, false);

            return $matchingItem ? -1 : 1;
        });

        $from = $from->filter(function ($fromItem) use ($destinations) {
            $matchingItem = $destinations->first(static fn ($el) => $el->id === $fromItem->id, false);

            return !$matchingItem;
        });

        /** @var Collection $points */
        $points = $from->concat($destinations)->unique();

        DB::transaction(static function () use ($task, $points) {
            $idx = 0;
            foreach ($points as $point) {
                $route = new Route();
                $route->sequence = $idx;
                $route->task_id = $task->id;
                $route->point_id = $point->id;
                $route->point_type = $point->type;
                $route->save();
                $idx++;
            }
        });

        return true;
    }
}
