<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\RoutePointTypeEnum;
use App\Models\Route;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;

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
        $remainingStartPointIds = new Collection();
        $remainingEndPointIds = new Collection();
        foreach ($orderUuidsInTask as $orderUuid) {
            try {
                $orderWorkflow = $this->workflowClient->newRunningWorkflowStub(
                    OrderWorkflowInterface::class,
                    'order:' . $orderUuid
                );
                $orderState = $orderWorkflow->getState();
                $remainingStartPointIds->push($orderState->startPointId);
                $remainingEndPointIds->push($orderState->endPointId);
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

        foreach (array_unique([$startPointIdToRemove, $endPointIdToRemove]) as $pointId) {
            $isStartPointInOtherOrders = $remainingStartPointIds->contains($pointId);
            $isEndPointInOtherOrders = $remainingEndPointIds->contains($pointId);
            /** @var Route|null $route */
            $route = $routes->firstWhere('point_id', $pointId);

            if (! $isStartPointInOtherOrders && ! $isEndPointInOtherOrders) {
                $route?->delete();

                continue;
            }

            if ($route === null) {
                continue;
            }

            $pointType = match (true) {
                $isStartPointInOtherOrders && $isEndPointInOtherOrders => RoutePointTypeEnum::INTERMEDIATE->value,
                $isStartPointInOtherOrders => RoutePointTypeEnum::START->value,
                default => RoutePointTypeEnum::FINISH->value,
            };

            if ($route->point_type !== $pointType) {
                $route->point_type = $pointType;
                $route->save();
            }
        }

        return [$startPointIdToRemove, $endPointIdToRemove];
    }
}
