<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\CreateOrderCommand;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderCreateFormResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Slot;
use App\Models\Task;
use App\Temporal\OrderWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Temporal\Client\WorkflowOptions;

class OrderCreateController extends Controller
{
    public function getOrderForm()
    {
        $dto = new class () {
            public array $emails;

            public Collection $points;
        };
        $dto->emails = Customer::limit(10)->get('email')->pluck('email')->toArray();
        $dto->points = Point::all(['id', 'address']);

        return OrderCreateFormResource::make($dto);
    }

    public function getOrdersByTask(string $taskUuid): JsonResource
    {
        $task = Task::where('uuid', $taskUuid)->firstOrFail();

        $orders = Order::with('startPoint', 'endPoint')
            ->where('task_id', $task->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return OrderResource::collection($orders);
    }

    public function createOrder(CreateOrderRequest $request)
    {
        $email = $request->get('customerEmail');
        $unitType = $request->get('unitType');
        $startPointId = $this->resolvePoint($request->array('startPoint'))->id;
        $endPointId = $this->resolvePoint($request->array('endPoint'))->id;
        $slotIds = collect($request->input('slotIds', []))
            ->when(
                $request->filled('slotId'),
                fn (Collection $ids): Collection => $ids->push((int) $request->input('slotId'))
            )
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $slots = Slot::query()
            ->whereIn('id', $slotIds->all())
            ->orderBy('from')
            ->get();

        $customer = Customer::where('email', $email)->firstOrFail();
        $orderUuid = Str::uuid()->toString();

        $timeRanges = $slots->map(static fn (Slot $selectedSlot): array => [
            'slot_id' => $selectedSlot->id,
            'date' => $selectedSlot->date ?? $request->get('date'),
            'from' => $selectedSlot->from,
            'to' => $selectedSlot->to,
        ])->all();

        $command = new CreateOrderCommand(
            orderUuid: $orderUuid,
            customerUuid: $customer->uuid,
            unitType: $unitType,
            startPointId: $startPointId,
            endPointId: $endPointId,
            timeRanges: $timeRanges,
        );

        $workflow = $this->workflowClient->newWorkflowStub(
            OrderWorkflowInterface::class,
            WorkflowOptions::new()
                ->withWorkflowId('order:'.$orderUuid)
                ->withWorkflowExecutionTimeout(CarbonInterval::days(30))
        );

        $this->workflowClient->start($workflow, $command);

        return response()->json(['data' => ['uuid' => $orderUuid]]);
    }

    private function resolvePoint(array $pointData): Point
    {
        return Point::firstOrCreate(
            [
                'address' => $pointData['address'],
                'lat' => (float) $pointData['lat'],
                'long' => (float) $pointData['lng'],
            ]
        );
    }
}
