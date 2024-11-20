<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\OrderCanBeUnassignedRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddOrderRequest extends FormRequest
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
            'orderUuid' => ['bail','required','string','exists:orders,uuid', new OrderCanBeUnassignedRule()],
        ];
    }
}
