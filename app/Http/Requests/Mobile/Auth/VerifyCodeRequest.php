<?php

namespace App\Http\Requests\Mobile\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'phone' => ['required', 'string', 'regex:/^\+77[0-9]{9}$/'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ];
    }
}
