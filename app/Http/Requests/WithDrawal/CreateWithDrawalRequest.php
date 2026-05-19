<?php

namespace App\Http\Requests\WithDrawal;

use Illuminate\Foundation\Http\FormRequest;

class CreateWithDrawalRequest extends FormRequest
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
            'user_id' => 'required',
            'club_id' => 'required',
            'amount' => 'required',
            'description' => 'sometimes',
            'payment_type' => 'required'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
            'club_id' => auth()->user()->club_id
        ]);
    }
}
