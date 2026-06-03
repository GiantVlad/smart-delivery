<?php

declare(strict_types=1);

namespace App\Dto;

class CreateTaskCommand
{
    public function __construct(
        public string $taskUuid,
        public string $courierUuid,
        public array $orderUuids,
    ) {}
}
