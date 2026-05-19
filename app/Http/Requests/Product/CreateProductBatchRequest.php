<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductBatchRequest extends FormRequest
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
    public function rules()
    {
        return [
            //'store_id' => 'required',
            //'quantity' => 'required',
            //'initial_quantity' => 'required',
            'purchase_price' => 'sometimes',
            'user_id' => 'required',
            'batches' => 'array|required'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            //'initial_quantity' => $this->quantity,
            'user_id' => auth()->id()
        ]);
    }
}
