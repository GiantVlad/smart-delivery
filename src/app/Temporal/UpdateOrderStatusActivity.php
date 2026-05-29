<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\OrderStatusEnum;
use App\Facades\CentrifugoFacade;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatusActivity implements UpdateOrderStatusActivityInterface
{
    public function updateOrderStatus(string $orderUuid, string $status): string
    {
        Log::info('Updating order status', [
            'order' => $orderUuid,
            'status' => $status,
        ]);

        $order = Order::where('uuid', $orderUuid)->firstOrFail();
        $order->status = $status;
        $order->delivered_at = $status === OrderStatusEnum::DELIVERED->value ? now() : null;
        if ($status === OrderStatusEnum::CANCELED->value) {
            $order->task_id = null;
        }
        $order->save();

        try {
            CentrifugoFacade::publish('order_status', ['order' => $orderUuid, 'status' => $status]);
            Log::info('Published order_status event', [
                'order' => $orderUuid,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish order_status event', [
                'order' => $orderUuid,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }

        return $orderUuid;
    }
}
