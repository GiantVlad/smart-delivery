<?php

declare(strict_types=1);

namespace App\Dto;

class TaskWorkflowState
{
    public function __construct(
        public string $taskUuid,
        public string $courierUuid,
        public string $status,
        public array $orderUuids,
        public array $terminalOrders,
    ) {
    }
}
