<?php

namespace App\Http\Requests\Client;

use App\Models\Trinket;
use Illuminate\Foundation\Http\FormRequest;

class WriteOffVisitRequest extends FormRequest
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
            'service_sale_id' => 'required',
            'trainer_id' => 'sometimes'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}
