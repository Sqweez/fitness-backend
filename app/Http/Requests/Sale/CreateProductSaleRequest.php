<?php

namespace App\Http\Requests\Sale;

use App\Rules\IsEnoughFunds;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductSaleRequest extends FormRequest
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
            'product_id' => 'sometimes',
            'club_id' => 'required',
            'amount' => ['required', 'min:0' , new IsEnoughFunds($this->client_id)],
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}
