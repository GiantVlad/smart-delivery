<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class OrdersCanBeAddedRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $orders = Order::whereIn('uuid', $value)->get();
        if ($orders->isEmpty() || $orders->count() !== count($value)) {
            $fail("Invalid list of orders.");
        }
        foreach ($orders as $order) {
            if ($order->status !== OrderStatusEnum::ACCEPTED->value) {
                $fail("Order $order->uuid with status $order->status can not be added to task.");
            }
            if ($order->task_id) {
                $fail("Order $order->uuid already assigned to a task.");
            }
        }
    }
}
