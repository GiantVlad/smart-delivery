<?php

declare(strict_types=1);

namespace App\Dto;

class CreateOrderCommand
{
    public bool $erpAcceptanceEnabled = true;

    public function __construct(
        public string $orderUuid,
        public string $customerUuid,
        public string $unitType,
        public int $startPointId,
        public int $endPointId,
        public array $timeRanges,
        bool $erpAcceptanceEnabled = true,
    ) {
        $this->erpAcceptanceEnabled = $erpAcceptanceEnabled;
    }
}
