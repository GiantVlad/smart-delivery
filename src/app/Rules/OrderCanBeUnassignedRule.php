<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class OrderCanBeUnassignedRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $order = Order::where('uuid', $value)->first();
        if (! in_array($order->status, [OrderStatusEnum::ASSIGNED->value, OrderStatusEnum::CANCELED->value], true)) {
            $fail("The order with status $order->status can not be unassigned.");
        }
    }
}
