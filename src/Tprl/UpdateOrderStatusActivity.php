<?php

declare(strict_types=1);

namespace Tprl;

use App\Models\Order;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class UpdateOrderStatusActivity implements UpdateOrderStatusActivityInterface
{
    public function updateOrderStatus(string $orderUuid, string $status): string
    {
        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        $order->status = $status;
        $order->save();

        return $orderUuid;
    }
}
