<?php

namespace App\Enums;

enum RoutePointTypeEnum: string
{
    case START = 'start';
    case FINISH = 'finish';
    case INTERMEDIATE = 'intermediate';
}
