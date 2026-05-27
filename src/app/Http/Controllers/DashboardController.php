<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CourierStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Order;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function deliveredOrdersTrend(): JsonResponse
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(9);

        $rows = Order::query()
            ->selectRaw('DATE(orders.delivered_at) as date')
            ->selectRaw('couriers.name as courier_name')
            ->selectRaw('COUNT(orders.id) as delivered_count')
            ->join('tasks', 'tasks.id', '=', 'orders.task_id')
            ->join('couriers', 'couriers.id', '=', 'tasks.courier_id')
            ->where('orders.status', OrderStatusEnum::DELIVERED->value)
            ->whereNotNull('orders.delivered_at')
            ->whereBetween(DB::raw('DATE(orders.delivered_at)'), [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->groupByRaw('DATE(orders.delivered_at), couriers.name')
            ->orderBy('date')
            ->get();

        $labels = [];
        for ($cursor = $startDate->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            $labels[] = $cursor->toDateString();
        }

        /** @var Collection<string, Collection<int, object>> $groupedByCourier */
        $groupedByCourier = $rows->groupBy('courier_name');
        $datasets = [];
        foreach ($groupedByCourier as $courierName => $courierRows) {
            $datasets[] = [
                'label' => $courierName,
                'data' => array_map(
                    static fn (string $date): int => (int) ($courierRows->firstWhere('date', $date)->delivered_count ?? 0),
                    $labels
                ),
            ];
        }

        return response()->json([
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
        ]);
    }

    public function activeCouriersMap(): JsonResponse
    {
        $tasks = Task::query()
            ->with([
                'courier',
                'routes' => static fn ($query) => $query
                    ->with('point')
                    ->orderByDesc('sequence'),
            ])
            ->whereNotIn('status', [
                TaskStatusEnum::FINISHED->value,
                TaskStatusEnum::CANCELED->value,
            ])
            ->whereHas('courier', static fn ($query) => $query
                ->where('status', CourierStatusEnum::OT->value))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->unique('courier_id');

        $couriers = $tasks
            ->map(function (Task $task): ?array {
                $route = $task->routes->first();
                $point = $route?->point;

                if (! $point || $point->lat === null || $point->long === null) {
                    return null;
                }

                return [
                    'courierUuid' => $task->courier->uuid,
                    'courierName' => $task->courier->name,
                    'courierStatus' => $task->courier->status,
                    'taskUuid' => $task->uuid,
                    'taskStatus' => $task->status,
                    'pointId' => $point->id,
                    'pointAddress' => $point->address,
                    'pointType' => $route->point_type,
                    'sequence' => $route->sequence,
                    'lat' => (float) $point->lat,
                    'lng' => (float) $point->long,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'data' => $couriers,
        ]);
    }
}
