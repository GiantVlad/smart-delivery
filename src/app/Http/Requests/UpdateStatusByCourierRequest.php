<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\OrderStatusEnum;
use App\Rules\OrderStatusCanBeChangedRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStatusByCourierRequest extends FormRequest
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
            'status' => ['string', new Enum(OrderStatusEnum::class)],
            'orderUuid' => [
                'bail',
                'required',
                'string',
                'exists:orders,uuid',
                new OrderStatusCanBeChangedRule($this->status),
            ],
        ];
    }
}
