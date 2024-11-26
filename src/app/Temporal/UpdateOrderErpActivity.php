<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\Http;

class UpdateOrderErpActivity implements UpdateOrderErpActivityInterface
{
    public function updateOrderInErp(OrderDto $orderDto): string
    {
        if (in_array($orderDto->status, [OrderStatusEnum::NEW->value, OrderStatusEnum::CANCELED->value])) {
            Http::accept('application/json')
                ->put(
                    'http://go-server:8090/erp',
                    ['orderUuid' => $orderDto->uuid, 'status' => $orderDto->status],
                )
                ->throw();
        }

        return $orderDto->uuid;
    }
}