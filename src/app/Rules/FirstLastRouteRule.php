<?php

namespace App\Rules;

use App\Models\Task;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;

class FirstLastRouteRule implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
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
        /** @var Collection $orders */
        $orders = Task::where('uuid', $this->data['taskUuid'])->orders()->get();
        $startPoints = $orders->flatMap(static fn ($order) => $order->start_point_id);
        $endPoints = $orders->flatMap(static fn ($order) => $order->start_point_id);

        if (! $startPoints->contains($value[0])) {
            $fail('Invalid first point in the route.');
        }

        if (! $endPoints->contains($value[count($value)-1])) {
            $fail('Invalid last point in the route.');
        }
    }
}
