<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Opekunov\Centrifugo\Contracts\CentrifugoInterface;
use Temporal\Client\WorkflowClientInterface;

abstract class Controller
{
    public function __construct(
        protected readonly WorkflowClientInterface $workflowClient,
        protected readonly CentrifugoInterface $centrifugo
    ) {}
}
