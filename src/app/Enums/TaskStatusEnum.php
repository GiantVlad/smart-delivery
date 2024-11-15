<?php

namespace App\Enums;

enum TaskStatusEnum: string
{
    case CREATED = 'created';
    case STARTED = 'started';
    case FINISHED = 'finished';
    case CANCELED = 'canceled';
}
