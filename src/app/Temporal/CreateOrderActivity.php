<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\OrderDto;
use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Str;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class CreateOrderActivity implements CreateOrderActivityInterface
{
    public function createOrder(OrderDto $orderDto): string
    {
        $customer = Customer::where('uuid', $orderDto->customerUuid)->firstOrFail();
        $order = new Order();
        $order->customer_id = $customer->id;
        $order->unit_type = $orderDto->unitType;
        $order->start_point_id = $orderDto->startPointId;
        $order->end_point_id = $orderDto->endPointId;
        $order->uuid = Str::uuid()->toString();
        $order->status = OrderStatusEnum::NEW;
        $order->save();

        return $order->uuid;
    }
}
