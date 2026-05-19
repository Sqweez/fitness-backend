<?php

namespace App\Http\Requests\Service;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class RestoreServiceRequest extends FormRequest
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
            'client_id' => 'required|exists:clients,id',
            'restore_price' => 'required',
            'restore_until' => 'required|date',
            'document' => 'sometimes|file',
            'service_id' => 'required',
            'is_accepted' => 'required',
            'previous_active_until' => 'required'
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            'is_accepted' =>
                $this->restore_price === $this->base_restore_price
                && !isset($this->document)
                && $this->restore_until === $this->base_restore_until,
            'user_id' => auth()->id(),
            'previous_active_until' => Carbon::parse($this->previous_active_until)
        ]);
    }
}
