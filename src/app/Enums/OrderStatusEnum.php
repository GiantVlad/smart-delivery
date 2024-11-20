<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case ACCEPTED = 'accepted';
    case ASSIGNED = 'assigned';
    case COLLECTED = 'collected';
    case DELIVERED = 'delivered';
    case CANCELED = 'canceled';

    public static function courierCanUpdate(): array
    {
        return [self::ACCEPTED->value, self::ASSIGNED->value, self::COLLECTED->value];
    }
}
