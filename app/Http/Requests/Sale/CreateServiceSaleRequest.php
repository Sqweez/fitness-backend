<?php

namespace App\Http\Requests\Sale;

use App\Models\Sale;
use App\Rules\IsEnoughFunds;
use Illuminate\Foundation\Http\FormRequest;

/* @mixin  Sale */

class CreateServiceSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'service_id' => 'sometimes',
            'club_id' => 'required',
            'amount' => ['required', 'min:0' , new IsEnoughFunds($this->client_id)],
            'is_prolongation' => 'required|boolean',
            'prolonged_id' => 'sometimes',
            'count' => 'required|integer',
            'self_name' => 'string'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
            'is_prolongation' => $this->is_prolongation ?: false,
            'prolonged_id' => $this->prolonged_id ?: null,
            'count' => $this->count ?: 1,
            'self_name' => $this->self_name ?: ''
        ]);
    }
}
