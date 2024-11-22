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
use App\Models\Task;
use App\Temporal\CreateOrderWorkflowInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Temporal\Client\WorkflowOptions;
use Carbon\CarbonInterval;

class OrderCreateController extends Controller
{
    public function getOrderForm()
    {
        $dto = new class {
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
        $startPointId = $request->get('startAddressId');
        $endPointId = $request->get('endAddressId');

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
        );

        $this->workflowClient->start($workflow, $orderDTO);

        return response()->json(['data' => true]);
    }
}
