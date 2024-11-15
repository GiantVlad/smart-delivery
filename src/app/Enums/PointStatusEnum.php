<?php

namespace App\Enums;

enum PointStatusEnum: string
{
    case NEXT = 'next';
    case VISITED = 'visited';
    case IN_TASK = 'in task';
    case MISSED = 'missed';
}
