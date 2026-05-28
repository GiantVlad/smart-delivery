<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\OrderDto;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderCreateFormResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Slot;
use App\Models\Task;
use App\Temporal\CreateOrderWorkflowInterface;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Temporal\Client\WorkflowOptions;

class OrderCreateController extends Controller
{
    public function getOrderForm()
    {
        $dto = new class
        {
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
        $slot = Slot::findOrFail((int) $request->get('slotId'));

        $workflow = $this->workflowClient->newWorkflowStub(
            CreateOrderWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $customer = Customer::where('email', $email)->firstOrFail();

        $orderDTO = new OrderDto(
            customerUuid: $customer->uuid,
            unitType: $unitType,
            startPointId: $startPointId,
            endPointId: $endPointId,
            from: $slot->from,
            to: $slot->to,
            date: Carbon::parse($slot->date ?? $request->get('date')),
        );

        $this->workflowClient->start($workflow, $orderDTO);

        return response()->json(['data' => true]);
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
