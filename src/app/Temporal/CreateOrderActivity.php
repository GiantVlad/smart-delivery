<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateOrderActivity implements CreateOrderActivityInterface
{
    public function createOrder(OrderDto $orderDto): string
    {
        $customer = Customer::where('uuid', $orderDto->customerUuid)->firstOrFail();
        $order = new Order;
        $order->customer_id = $customer->id;
        $order->unit_type = $orderDto->unitType;
        $order->start_point_id = $orderDto->startPointId;
        $order->end_point_id = $orderDto->endPointId;
        $primaryRange = collect($orderDto->timeRanges)
            ->first(static fn (array $range): bool => isset($range['date']));
        $order->date = $primaryRange['date'] ?? now()->toDateString();
        if (Schema::hasColumn('orders', 'time_ranges')) {
            $order->time_ranges = $orderDto->timeRanges;
        }
        $order->uuid = Str::uuid()->toString();
        $order->status = OrderStatusEnum::NEW;
        $order->save();

        return $order->uuid;
    }
}
