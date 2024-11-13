<?php

namespace App\Enums;

enum CourierStatusEnum: string
{
    case RD = 'ready';
    case OT = 'on_task';
    case OH = 'on_hold';
    case NW = 'not_working';
}
