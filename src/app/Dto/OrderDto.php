<?php

declare(strict_types=1);

namespace App\Dto;

use Spatie\LaravelData\Data;

class OrderDto extends Data
{
    public array $timeRanges = [];

    public array $time_ranges = [];

    public function __construct(
        public string $customerUuid,
        public string $unitType,
        public int $startPointId,
        public int $endPointId,
        ?array $timeRanges = null,
        public string $uuid = '',
        public string $status = '',
    ) {
        $this->timeRanges = $timeRanges ?? [];
        $this->time_ranges = $this->timeRanges;
    }

    public function normalizedTimeRanges(): array
    {
        if (! empty($this->timeRanges)) {
            return $this->timeRanges;
        }

        if (! empty($this->time_ranges)) {
            return $this->time_ranges;
        }

        return [];
    }
}
