<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
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
            'first_name' => 'bail|required|string|min:2|max:100',
            'last_name' => 'bail|required|string|min:2|max:100',
            'email' => 'bail|required|email|min:3|max:100',
            'phone' => 'required|numeric|min:8|max:11',
        ];
    }
}
