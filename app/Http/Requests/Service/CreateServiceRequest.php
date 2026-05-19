<?php

namespace App\Http\Requests\Service;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\RequiredIf;

class CreateServiceRequest extends FormRequest
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
            'description' => 'sometimes',
            'club_id' => 'required',
            'service_type_id' => 'required',
            'price' => 'required',
            'validity_days' => 'required',
            'unlimited_price' => 'sometimes',
            'entries_count' => new RequiredIf($this->service_type_id === Service::TYPE_PROGRAM),
            'validity_minutes' => new RequiredIf($this->service_type_id === Service::TYPE_SOLARIUM),
            'restore_price' => 'sometimes'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'entries_count' => $this->service_type_id === Service::TYPE_UNLIMITED ? null : $this->entries_count,
            'unlimited_price' =>
                in_array(
                    $this->service_type_id, [Service::TYPE_UNLIMITED, Service::TYPE_PROGRAM]
                ) ? $this->unlimited_price : $this->price,
            'validity_days' =>
                in_array(
                    $this->service_type_id, [Service::TYPE_UNLIMITED, Service::TYPE_PROGRAM]
                ) ? $this->validity_days : 15000,
        ]);
    }
}
