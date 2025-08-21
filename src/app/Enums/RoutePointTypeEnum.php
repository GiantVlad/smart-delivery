<?php

namespace App\Enums;

enum RoutePointTypeEnum: string
{
    case START = 'pick up';
    case FINISH = 'delivery';
    case INTERMEDIATE = 'delivery and pick up';
}
