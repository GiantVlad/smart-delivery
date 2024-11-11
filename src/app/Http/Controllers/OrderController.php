<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowClientInterface;
use Carbon\CarbonInterval;
use Tprl\CreateOrderWorkflowInterface;

class OrderController extends Controller
{
    public function __construct(private WorkflowClientInterface $workflowClient)
    {}

    //getOrderForm
    public function getOrderForm()
    {
        return view('new-order');
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

        return response()->json('Done');
    }
}
