<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'pass' => ['required_without_all:login,password', 'string', 'max:255'],
            'login' => ['required_without:pass', 'string', 'max:255'],
            'password' => ['required_without:pass', 'string', 'max:255'],
        ];
    }
}
