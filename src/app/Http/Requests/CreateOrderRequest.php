<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'startPoint' => 'required|array',
            'startPoint.address' => 'required|string|max:255',
            'startPoint.lat' => 'required|numeric|between:-90,90',
            'startPoint.lng' => 'required|numeric|between:-180,180',
            'startPoint.placeId' => 'nullable|string|max:255',
            'endPoint' => 'required|array',
            'endPoint.address' => 'required|string|max:255|different:startPoint.address',
            'endPoint.lat' => 'required|numeric|between:-90,90',
            'endPoint.lng' => 'required|numeric|between:-180,180',
            'endPoint.placeId' => 'nullable|string|max:255',
            'slotIds' => 'required|array|min:1',
            'slotIds.*' => 'integer|exists:slots,id|distinct',
            'date' => 'required|date|after:yesterday',
        ];
    }
}
