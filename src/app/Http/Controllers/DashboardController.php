<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
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
            ->groupBy('date', 'courier_name')
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
}
