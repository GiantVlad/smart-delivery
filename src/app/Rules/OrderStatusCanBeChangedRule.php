<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class OrderStatusCanBeChangedRule implements DataAwareRule, ValidationRule
{
    private string $status;

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $order = Order::whereUuid($value)->first();
        $canBeChangedTo = OrderStatusEnum::canBeChangedTo(OrderStatusEnum::tryFrom($order->status));
        if (! in_array(OrderStatusEnum::tryFrom($this->status), $canBeChangedTo, true)) {
            $fail("The order with status $order->status can not be changed to " . $this->status);
        }
    }
}
