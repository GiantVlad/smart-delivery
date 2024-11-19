<?php

namespace App\Http\Requests;

use App\Rules\FirstLastRouteRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\Attributes\Validation\Uppercase;

class EditRouteRequest extends FormRequest
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
            'points' => ['required', 'array', 'min:1', (new FirstLastRouteRule())->setData(['taskUuid' => $this->request->get('taskUuid')])],
        ];
    }
}
