<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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

    public function createOrder(string $name)
    {
        $workflow = $this->workflowClient->newWorkflowStub(
            CreateOrderWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $customer = Customer::first();
        $unitType = 'small';

        $this->workflowClient->start($workflow, $customer, $unitType);

        return response()->json('Done');
    }
}
