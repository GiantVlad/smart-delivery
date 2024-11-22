<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CourierStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customerEmail' => 'required|email',
            'unitType' => 'required|string|min:3|max:20',
            'startPointId' => 'required|numeric|exists:points,id',
            'endPointId' => 'required|numeric|different:startPointId|exists:points,id',
        ];
    }
}
