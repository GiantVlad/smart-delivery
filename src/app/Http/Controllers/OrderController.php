<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowClientInterface;
use Carbon\CarbonInterval;
use Tprl\CreateOrderWorkflowInterface;
use Tprl\OrderStatusHandlerWorkflowInterface;

class OrderController extends Controller
{
    public function __construct(private WorkflowClientInterface $workflowClient)
    {}

    //getOrderForm
    public function getOrderForm()
    {
        return view('new-order');
    }

    public function createOrder(Request $request): JsonResponse
    {
        $email = $request->get('email');
        $unitType = $request->get('unitType');

        $workflow = $this->workflowClient->newWorkflowStub(
            CreateOrderWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $customer = Customer::where('email', $email)->firstOrFail();

        $this->workflowClient->start($workflow, $customer->uuid, $unitType);

        return response()->json('Created', 201);
    }

    public function confirmOrder(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            '<--WorkflowId-->',
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ACCEPTED->value);

        return response()->json('Updated');
    }
}
