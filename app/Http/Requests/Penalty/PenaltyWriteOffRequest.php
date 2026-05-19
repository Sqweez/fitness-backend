<?php

namespace App\Http\Requests\Penalty;

use Illuminate\Foundation\Http\FormRequest;

class PenaltyWriteOffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'client_id' => 'required',
            'user_id' => 'required',
            'trainer_id' => 'sometimes',
            'description' => 'required',
            'service_sale_id' => 'required',
            'penalty_date' => 'required|date'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id()
        ]);
    }
}
