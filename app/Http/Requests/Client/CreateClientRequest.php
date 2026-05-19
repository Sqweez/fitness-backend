<?php

namespace App\Http\Requests\Client;

use App\Rules\NotBusyPass;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateClientRequest extends FormRequest
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
            'name' => 'required|string',
            'phone' => [
                'required',
                'string',
                //Rule::unique('clients')
            ],
            'birth_date' => 'required|date|before:today|after:' . now()->subYears(100),
            'pass' => ['sometimes', new NotBusyPass],
            'comment' => 'sometimes',
            'gender' => 'required',
            'photo' => 'sometimes|file',
            'user_id' => 'required',
            'club_id' => 'required',
            'cached_pass' => 'sometimes',
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'user_id' => auth()->id(),
            'birth_date' => Carbon::parse($this->birth_date)->format('Y-m-d'),
            'phone' => unmask_phone($this->phone),
            'cached_pass' => $this->pass,
        ]);
    }
}
