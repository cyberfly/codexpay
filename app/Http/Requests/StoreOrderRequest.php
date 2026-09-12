<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
