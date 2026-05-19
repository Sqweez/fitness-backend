<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class TopUpClientAccountRequest extends FormRequest
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
            'client_id' => 'required',
            'user_id' => 'required',
            'club_id' => 'required',
            'amount' => 'required|min:1',
            'payment_type' => 'required',
            'description' => 'required'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
            'description' => $this->description ?: 'Пополнение средств пользователем ' . auth()->user()->name,
        ]);
    }
}
