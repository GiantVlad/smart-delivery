<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\HolidayReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AddCourierHolidaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courier_id' => [
                'required',
                'integer',
                'exists:couriers,id',
            ],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'reason_code' => ['sometimes', 'integer', new Enum(HolidayReason::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
