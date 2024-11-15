<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case ACCEPTED = 'accepted';
    case ASSIGNED = 'assigned';
    case FINISHED = 'finished';
    case CANCELED = 'canceled';
}
