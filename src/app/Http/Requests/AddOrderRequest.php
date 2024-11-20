<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\OrdersCanBeAddedRule;
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
            'taskUuid' => 'bail|required|string|exists:tasks,uuid',
            'orderUuids' => ['required', 'array', 'min:1', new OrdersCanBeAddedRule()],
        ];
    }
}
