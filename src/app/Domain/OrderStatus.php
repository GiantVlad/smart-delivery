<?php

namespace App\Domain;

use App\Enums\OrderStatusEnum;
use App\Models\Task;

class OrderStatus
{
    public function isOneOrderLeft(Task $task)
    {
        if ($task->orders()->where('status', '!=', OrderStatusEnum::DELIVERED->value)->count() > 1) {
            return false;
        }

        return true;
    }
}
