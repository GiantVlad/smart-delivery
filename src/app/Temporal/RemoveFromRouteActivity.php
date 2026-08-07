<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\RoutePointTypeEnum;
use App\Models\Route;
use App\Models\Task;
use Illuminate\Support\Collection;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;
use Illuminate\Support\Facades\Log;

class RemoveFromRouteActivity implements RemoveFromRouteActivityInterface
{
    public function __construct(private WorkflowClientInterface $workflowClient)
    {
    }

    public function removeFromRoute(
        string $taskUuid,
        array $orderUuidsInTask,
        int $startPointIdToRemove,
        int $endPointIdToRemove,
    ): array {
        // Build a collection of start and end points from the remaining orders
        $remainingOrderPoints = new Collection();
        foreach ($orderUuidsInTask as $orderUuid) {
            try {
                $orderWorkflow = $this->workflowClient->newRunningWorkflowStub(
                    OrderWorkflowInterface::class,
                    'order:' . $orderUuid
                );
                $orderState = $orderWorkflow->getState();
                $remainingOrderPoints->push($orderState->startPointId);
                $remainingOrderPoints->push($orderState->endPointId);
            } catch (WorkflowNotFoundException $e) {
                // Log warning, but continue if an order workflow is not found
                Log::warning('Order workflow not found when removing from route', [
                    'orderUuid' => $orderUuid,
                    'taskUuid' => $taskUuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $routes = Route::where('task_id', Task::where('uuid', $taskUuid)->value('id'))->get();

        foreach ([$startPointIdToRemove, $endPointIdToRemove] as $idx => $pointId) {
            $isStartPointInOtherOrders = $remainingOrderPoints->contains($pointId);
            $isEndPointInOtherOrders = $remainingOrderPoints->contains($pointId);

            if (! $isStartPointInOtherOrders && ! $isEndPointInOtherOrders) {
                /** @var Route|null $route */
                $route = $routes->firstWhere('point_id', $pointId);
                $route?->delete();
            } else {
                $route = $routes->firstWhere('point_id', $pointId);
                if ($route !== null
                    && $route->point_type === RoutePointTypeEnum::INTERMEDIATE->value
                    && (! $isStartPointInOtherOrders || ! $isEndPointInOtherOrders)
                ) {
                    $route->point_type = $idx === 0
                        ? RoutePointTypeEnum::FINISH->value
                        : RoutePointTypeEnum::START->value;
                    $route->save();
                }
            }

            if ($isStartPointInOtherOrders && $isEndPointInOtherOrders) {
                $route = $routes->firstWhere('point_id', $pointId);

                $route?->delete();
            }
        }

        return [$startPointIdToRemove, $endPointIdToRemove];
    }
}
