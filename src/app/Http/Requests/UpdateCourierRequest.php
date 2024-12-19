<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CourierStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCourierRequest extends FormRequest
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
            'uuid' => 'bail|required|string|exists:couriers,uuid',
            'name' => 'bail|string|min:3|max:100',
            'phone' => 'bail|numeric|min:8|max:11',
            'status' => ['string', new Enum(CourierStatusEnum::class)],
        ];
    }
}
