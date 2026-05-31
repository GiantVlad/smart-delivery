<?php

declare(strict_types=1);

namespace App\Dto;

class OrderDto
{
    public string $customerUuid = '';

    public string $unitType = '';

    public int $startPointId = 0;

    public int $endPointId = 0;

    public array $timeRanges = [];

    public string $uuid = '';

    public string $status = '';

    public function __construct(
        string $customerUuid = '',
        string $unitType = '',
        int $startPointId = 0,
        int $endPointId = 0,
        array $timeRanges = [],
        string $uuid = '',
        string $status = '',
    ) {
        $this->customerUuid = $customerUuid;
        $this->unitType = $unitType;
        $this->startPointId = $startPointId;
        $this->endPointId = $endPointId;
        $this->timeRanges = $timeRanges;
        $this->uuid = $uuid;
        $this->status = $status;
    }

    public function normalizedTimeRanges(): array
    {
        if (! empty($this->timeRanges)) {
            return $this->timeRanges;
        }

        return [];
    }
}
