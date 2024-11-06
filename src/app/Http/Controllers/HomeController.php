<?php

namespace App\Http\Controllers;

use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowClientInterface;
use Carbon\CarbonInterval;
use Tprl\GreetingWorkflowInterface;

class HomeController extends Controller
{
    public function __construct(private WorkflowClientInterface $workflowClient)
    {}

    public function greet(string $name)
    {
        $workflow = $this->workflowClient->newWorkflowStub(
            GreetingWorkflowInterface::class,
            WorkflowOptions::new()->withWorkflowExecutionTimeout(CarbonInterval::minute())
        );

        $run = $this->workflowClient->start($workflow, $name ?? 'Vlad');
        
        return response()->json('Done');
    }

    public function greetComplete(string $token)
    {
        // @@@SNIPSTART samples-php-async-activity-completion-completebytoken
        $client = $this->workflowClient->newActivityCompletionClient();
        // Complete the Activity.
        $client->completeByToken(
            base64_decode($token),
            'Activity Completed by token!'
        );
        // @@@SNIPEND

        //return self::SUCCESS;
        return response()->json('Completed');
    }
}
