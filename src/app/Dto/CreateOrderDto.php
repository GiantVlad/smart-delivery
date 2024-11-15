<?php

declare(strict_types=1);

namespace App\Dto;

use Spatie\LaravelData\Data;

class CreateOrderDto extends Data
{
    public function __construct(
        public string $customerUuid,
        public string $unitType,
        public string $startPointId,
        public string $endPointId,
    ) {
    }
}
