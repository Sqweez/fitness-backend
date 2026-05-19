<?php

namespace App\Http\Requests\Economy;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class GetReportsRequest extends FormRequest
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
            'start' => 'required',
            'finish' => 'required'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'start' => $this->start ? Carbon::parse($this->start) : now(),
            'finish' => $this->finish ? Carbon::parse($this->finish) : now(),
        ]);
    }
}
