<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CourierStatusEnum;
use App\Rules\OrderStatusCanBeChangedRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;

class OrderConfirmationRequest extends FormRequest
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
        Log::info('OrderConfirmationRequest uuid: ' . $this->orderUuid);
        Log::info('OrderConfirmationRequest status: ' . $this->status);

        return [
            'orderUuid' => [
                'bail',
                'required',
                'string',
                'exists:orders,uuid',
                new OrderStatusCanBeChangedRule($this->status),
            ],
            'status' => ['bail', 'required', 'string', new Enum(CourierStatusEnum::class)],
        ];
    }
}
