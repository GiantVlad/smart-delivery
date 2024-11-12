<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case UNDEFINED = 'undefined';
    case CREATED = 'created';
    case ACCEPTED = 'accepted';
}
