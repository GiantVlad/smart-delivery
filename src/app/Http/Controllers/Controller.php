<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Temporal\OrderStatusHandlerWorkflowInterface;
use Illuminate\Support\Facades\Cache;
use Temporal\Client\WorkflowClientInterface;

abstract class Controller
{
    public function __construct(protected WorkflowClientInterface $workflowClient)
    {}

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getWfId(): string
    {
        if (Cache::has(OrderStatusHandlerWorkflowInterface::WORKFLOW_STATUS_HANDLER_KEY)) {
            return Cache::get(OrderStatusHandlerWorkflowInterface::WORKFLOW_STATUS_HANDLER_KEY);
        }

        throw new \Exception('workflow ID is not defined in cache');
    }
}
