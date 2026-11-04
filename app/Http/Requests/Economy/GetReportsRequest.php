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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = $this->start->copy()->startOfDay();
            $finish = $this->finish->copy()->startOfDay();

            if ($finish->lt($start)) {
                $validator->errors()->add('finish', 'Конечная дата не может быть раньше начальной');
            }
        });
    }

    protected function prepareForValidation() {
        $this->merge([
            'start' => $this->start ? Carbon::parse($this->start) : now(),
            'finish' => $this->finish ? Carbon::parse($this->finish) : now(),
        ]);
    }
}
