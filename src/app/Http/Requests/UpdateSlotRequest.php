<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Slot;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:slots,id', 'integer'],
            'capacity' => ['required', 'integer', 'min:1', 'max:' . Slot::MAX_CAPACITY],
        ];
    }
}
