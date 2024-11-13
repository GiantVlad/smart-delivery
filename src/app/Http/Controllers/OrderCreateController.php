<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Temporal\CreateOrderWorkflowInterface;
use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;
use Carbon\CarbonInterval;

class OrderCreateController extends Controller
{
    //getOrderForm
    public function getOrderForm()
    {
        $customerEmails = Customer::limit(10)->get('email')->pluck('email')->toArray();

        return view('new-order', ['customerEmails' => $customerEmails]);
    }

    public function getOrders()
    {
        $orders = Order::with('customer')
            ->limit(30)
            ->orderBy('updated_at', 'desc')
            ->get();
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
}
