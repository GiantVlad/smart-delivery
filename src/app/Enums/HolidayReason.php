<?php

namespace App\Enums;

enum HolidayReason: int
{
    case VACATION = 0;
    case SICK_LEAVE = 1;
    case DAY_OFF = 2;
    case PUBLIC_HOLIDAY = 3;

    public static function getLabel(self $value): string
    {
        return match($value) {
            self::VACATION => 'Vacation',
            self::SICK_LEAVE => 'Sick Leave',
            self::DAY_OFF => 'Day Off',
            self::PUBLIC_HOLIDAY => 'Public Holiday',
        };
    }

    public static function toArray(): array
    {
        return [
            self::VACATION->value => self::getLabel(self::VACATION),
            self::SICK_LEAVE->value => self::getLabel(self::SICK_LEAVE),
            self::DAY_OFF->value => self::getLabel(self::DAY_OFF),
            self::PUBLIC_HOLIDAY->value => self::getLabel(self::PUBLIC_HOLIDAY),
        ];
    }
}
