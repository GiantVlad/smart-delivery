<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WeekDaysEnum;
use App\Models\WorkingHour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateWorkingHoursRequest extends FormRequest
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
                function ($attribute, $value, $fail) {
                    $exists = WorkingHour::where('courier_id', $value)
                        ->where('day', $this->day)
                        ->exists();

                    if ($exists) {
                        $fail('Working hours for this courier on the selected day already exist.');
                    }
                }
            ],
            'day' => ['required', new Enum(WeekDaysEnum::class)],
            'from' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'to' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', 'after_or_equal:from'],
        ];
    }

    public function messages()
    {
        return [
            'to.after_or_equal' => 'The end time must be after or equal to the start time.',
        ];
    }
}
