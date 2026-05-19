<?php

namespace App\Http\Requests\Client;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StopCardRequest extends FormRequest
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
            'service_sale_id' => 'required',
            'active_until_prev' => 'required',
            'client_id' => 'required',
            'user_id' => 'required',
            'is_active' => 'required',
            'description' => 'sometimes',
            'remaining_days' => 'required'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
            'active_until_prev' => Carbon::parse($this->active_until_prev),
            'remaining_days' => now()->diffInDays(Carbon::parse($this->active_until_prev)) + 1,
        ]);
    }
}
