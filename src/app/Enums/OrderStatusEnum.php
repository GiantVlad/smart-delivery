<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case ACCEPTED = 'accepted';
    case ASSIGNED = 'assigned';
    case STARTED = 'started';
    case FINISHED = 'finished';
    case CANCELED = 'canceled';

    public static function courierCanUpdate(): array
    {
        return [self::ASSIGNED->value, self::STARTED->value, self::ACCEPTED->value];
    }
}
