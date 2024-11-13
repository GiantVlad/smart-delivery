<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Enums\OrderStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Str;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

class CreateOrderActivity implements CreateOrderActivityInterface
{
    public function createOrder(string $customerUuid, string $unitType): string
    {
        $customer = Customer::where('uuid', $customerUuid)->firstOrFail();
        $order = new Order();
        $order->customer_id = $customer->id;
        $order->unit_type = $unitType;
        $order->uuid = Str::uuid()->toString();
        $order->status = OrderStatusEnum::NEW;
        $order->save();

        return $order->uuid;
    }
}
