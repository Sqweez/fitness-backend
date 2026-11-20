<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ChatPageRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'limit' => ['required', 'integer', 'min:1', 'max:100'],
            'cursor' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'limit' => $this->input('limit', 30),
            'cursor' => $this->input('cursor', 0),
        ]);
    }
}
