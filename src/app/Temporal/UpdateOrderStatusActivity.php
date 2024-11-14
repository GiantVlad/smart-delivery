<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Courier;
use App\Models\Order;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class UpdateOrderStatusActivity implements UpdateOrderStatusActivityInterface
{
    public function updateOrderStatus(string $orderUuid, string $status, ?string $courierUuid = null): string
    {
        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        if ($courierUuid) {
            $courier = Courier::where('uuid', $courierUuid)->firstOrFail();
            $order->courier_id = $courier->id;
        }
        $order->status = $status;
        $order->save();

        return $orderUuid;
    }
}
