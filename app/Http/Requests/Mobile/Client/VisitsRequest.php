<?php

namespace App\Http\Requests\Mobile\Client;

use Illuminate\Foundation\Http\FormRequest;

class VisitsRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'limit' => ['required', 'integer', 'min:1', 'max:50'],
            'cursor' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'limit' => $this->input('limit', 20),
            'cursor' => $this->input('cursor', 0),
        ]);
    }
}
