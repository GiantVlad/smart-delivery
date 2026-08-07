<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderCommand;
use App\Dto\OrderWorkflowState;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'OrderProjectionActivity.')]
interface OrderProjectionActivityInterface
{
    #[ActivityMethod(name: 'Create')]
    public function create(CreateOrderCommand $command): string;

    #[ActivityMethod(name: 'Update')]
    public function update(OrderWorkflowState $state): string;
}
