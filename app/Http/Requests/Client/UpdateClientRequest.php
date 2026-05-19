<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use App\Rules\NotBusyPass;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
            'club_id' => 'required',
            'pass' => ['sometimes', new NotBusyPass($this->id, Client::class)],
            'description' => 'sometimes',
            'photo' => 'sometimes|file',
            'birth_date' => 'required',
            'cached_pass' => 'sometimes',
            'phone' => [
                'required',
                // @TODO 2023-02-20T21:49:15 временное решение для детей
               /* Rule::unique('clients', 'phone')
                    ->whereNot('id', $this->id)*/
            ]
        ];
    }

    protected function prepareForValidation() {
        $client = Client::find($this->id);
        $this->merge([
            'phone' => unmask_phone($this->phone),
            'description' => $this->description === 'null' ? '' : $this->description,
            //'cached_pass' => $this->pass,
            'birth_date' => $this->birth_date ? Carbon::parse($this->birth_date) : $client->birth_date,
        ]);
    }
}
