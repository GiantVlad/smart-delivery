<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\FirstLastRouteRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        if (!$this->taskUuid) {
            throw new \Exception('no taskUUID');
        }
        return [
            'taskUuid' => 'bail|required|string|exists:tasks,uuid',
            'points' => [
                'required',
                'array',
                'min:1',
                (new FirstLastRouteRule())->setData(['taskUuid' => $this->taskUuid]),
            ],
        ];
    }
}
