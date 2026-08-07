<?php

declare(strict_types=1);

namespace App\Dto;

class OrderWorkflowState
{
    public function __construct(
        public string $orderUuid,
        public string $customerUuid,
        public string $status,
        public ?string $taskUuid,
        public string $unitType,
        public int $startPointId,
        public int $endPointId,
        public array $timeRanges,
    ) {
    }
}
