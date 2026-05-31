<?php

declare(strict_types=1);

namespace App\Dto;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class OrderDto extends Data
{
    public array $timeRanges = [];

    public function __construct(
        public string $customerUuid,
        public string $unitType,
        public int $startPointId,
        public int $endPointId,
        public string $from,
        public string $to,
        public Carbon $date,
        ?array $timeRanges = null,
        public string $uuid = '',
        public string $status = '',
    ) {
        $this->timeRanges = $timeRanges ?? [];
    }
}
