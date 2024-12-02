<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Opekunov\Centrifugo\Centrifugo;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class UpdateOrderStatusActivity implements UpdateOrderStatusActivityInterface
{
    public function __construct(private readonly Centrifugo $centrifugo)
    {
    }

    public function updateOrderStatus(string $orderUuid, string $status): string
    {
        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        $order->status = $status;
        if ($status === OrderStatusEnum::CANCELED->value) {
            $order->task_id = null;
        }
        $order->save();

        $this->centrifugo->publish('order_status', ['order' => $orderUuid, 'status' => $status]);

        return $orderUuid;
    }
}
