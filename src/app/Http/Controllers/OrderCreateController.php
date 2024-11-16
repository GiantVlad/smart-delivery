<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\CreateOrderDto;
use App\Http\Resources\OrderCreateFormResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Temporal\CreateOrderWorkflowInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;
use Carbon\CarbonInterval;

class OrderCreateController extends Controller
{
    public function getOrderForm()
    {
        $customerEmails = Customer::limit(10)->get('email')->pluck('email')->toArray();

        $points = Point::all(['id', 'address']);

        return OrderCreateFormResource::make(['emails' => $customerEmails, 'points' => $points]);
    }

    public function getOrders(): JsonResource
    {
        $orders = Order::with('customer', 'task.courier', 'startPoint', 'endPoint')
            ->limit(30)
            ->orderBy('updated_at', 'desc')
            ->get();

        return OrderResource::collection($orders);
    }

    public function createOrder(Request $request)
    {
        $email = $request->get('email');
        $unitType = $request->get('unitType');
        $startPointId = $request->get('startAddressId');
        $endPointId = $request->get('endAddressId');

        $workflow = $this->workflowClient->newWorkflowStub(
            CreateOrderWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $customer = Customer::where('email', $email)->firstOrFail();

        $orderDTO = new CreateOrderDto(
            customerUuid: $customer->uuid,
            unitType: $unitType,
            startPointId: $startPointId,
            endPointId: $endPointId,
        );

        $this->workflowClient->start($workflow, $orderDTO);

        return redirect('/orders');
    }
}
