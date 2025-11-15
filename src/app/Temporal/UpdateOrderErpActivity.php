<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class UpdateOrderErpActivity implements UpdateOrderErpActivityInterface
{
    public function updateOrderInErp(): array
    {
        $updatesOrders = [];
        $orders = Order::whereIn('status', [OrderStatusEnum::CANCELED->value, OrderStatusEnum::NEW->value])->get();
        foreach ($orders as $order) {
            if (in_array($order->status, [OrderStatusEnum::NEW->value, OrderStatusEnum::CANCELED->value])) {
                Http::accept('application/json')
                    ->post(
                        'http://go-server:8090/erp',
                        ['orderUuid' => $order->uuid, 'status' => $order->status],
                    )
                    ->throw();
                $updatesOrders[] = $order->uuid;
            }
        }

        return $updatesOrders;
    }
}
