<?php

declare(strict_types=1);

namespace App\Dto;

class OrderDto
{
    public function __construct(
        public string $customerUuid,
        public string $unitType,
        public int $startPointId,
        public int $endPointId,
        public array $timeRanges,
        public string $uuid = '',
        public string $status = '',
    ) {}
}
