<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
