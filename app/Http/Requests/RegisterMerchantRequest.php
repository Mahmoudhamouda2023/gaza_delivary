<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'store_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:256', 'confirmed', Password::min(8)->letters()->numbers()],
            'password_confirmation' => ['required', 'string', 'max:256'],
        ];
    }
}
