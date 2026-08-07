<?php

declare(strict_types=1);

namespace App\Dto;

use Spatie\LaravelData\Data;

class AssignOrderDto extends Data
{
    public function __construct(
        public array $orderCustomerUuids,
        public string $taskUuid,
        public string $courierUuid,
    ) {
    }
}
