<?php

namespace App\Http\Controllers;

use Temporal\Client\WorkflowClientInterface;

abstract class Controller
{
    public function __construct(protected WorkflowClientInterface $workflowClient)
    {}
}
