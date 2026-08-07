<?php

declare(strict_types=1);

namespace App\Dto;

use Spatie\LaravelData\Data;

class SlotDto extends Data
{
    public function __construct(
        public string $from,
        public string $to,
    ) {
    }
}
