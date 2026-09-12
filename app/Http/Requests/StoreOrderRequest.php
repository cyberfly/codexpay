<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

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
            'submission_token' => ['required', 'uuid'],
        ];
    }

    /**
     * Add validation for coupon use that depends on the authenticated user.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('coupon_code')) {
                return;
            }

            $user = $this->user();

            if (! $user instanceof User || ! $user->hasVerifiedEmail()) {
                $validator->errors()->add(
                    'coupon_code',
                    'Sila log masuk dengan e-mel yang telah disahkan untuk menggunakan kupon.',
                );

                return;
            }

            if ($validator->errors()->has('customer_email')) {
                return;
            }

            if (! hash_equals(Str::lower($user->email), Str::lower($this->string('customer_email')->toString()))) {
                $validator->errors()->add(
                    'customer_email',
                    'E-mel tempahan mesti sepadan dengan e-mel akaun yang telah disahkan untuk menggunakan kupon.',
                );
            }
        }];
    }
}
