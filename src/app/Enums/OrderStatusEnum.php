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

    public static function canBeChangedTo(self $status): array
    {
        return match ($status) {
            self::NEW, self::CANCELED => [self::ACCEPTED],
            self::ACCEPTED => [self::ASSIGNED, self::CANCELED],
            self::ASSIGNED => [self::COLLECTED, self::CANCELED],
            self::COLLECTED => [self::DELIVERED, self::CANCELED],
            default => [],
        };
    }
}
