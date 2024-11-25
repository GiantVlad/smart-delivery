<?php

declare(strict_types=1);

namespace App\Dto;

use Spatie\LaravelData\Data;

class TaskDto extends Data
{
    public function __construct(
        public string $courierUuid,
        public array $orderUuids,
        public string $orderStatusWFId,
        public ?string $taskUuid = null,
    ) {
    }
}
