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
        $customerEmails = Customer::limit(10)->get('email')->pluck('email')->toArray();
        return view('new-order', ['customerEmails' => $customerEmails]);
    }

    public function getOrders()
    {
        $orders = Order::with('customer')->limit(30)->orderBy('updated_at', 'desc')->get();
        return view('orders', ['orders' => $orders]);
    }

    public function createOrder(Request $request)
    {
        $email = $request->get('email');
        $unitType = $request->get('unitType');

        $workflow = $this->workflowClient->newWorkflowStub(
            CreateOrderWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $customer = Customer::where('email', $email)->firstOrFail();

        $this->workflowClient->start($workflow, $customer->uuid, $unitType);

        return redirect('/orders');
    }

    public function confirmOrder(Request $request): JsonResponse
    {
        $orderUuid = $request->get('orderUuid');
        Order::where('uuid', $orderUuid)->firstOrFail();
        $workflow = $this->workflowClient->newRunningWorkflowStub(
            OrderStatusHandlerWorkflowInterface::class,
            '5bd134de-8b6c-4cdb-a9d2-e4750a4847cb',
        );

        $workflow->updateStatus($orderUuid, OrderStatusEnum::ACCEPTED->value);

        return response()->json('Updated');
    }
}
