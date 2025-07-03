<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\OrderStatusEnum;
use App\Facades\CentrifugoFacade;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Opekunov\Centrifugo\Exceptions\CentrifugoException;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class UpdateOrderStatusActivity implements UpdateOrderStatusActivityInterface
{
    public function updateOrderStatus(string $orderUuid, string $status): string
    {
        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        $order->status = $status;
        if ($status === OrderStatusEnum::CANCELED->value) {
            $order->task_id = null;
        }
        $order->save();

        try {
            CentrifugoFacade::publish('order_status', ['order' => $orderUuid, 'status' => $status]);
        } catch (\Throwable $e) {
            Log::error($e);
        }

        return $orderUuid;
    }
}
