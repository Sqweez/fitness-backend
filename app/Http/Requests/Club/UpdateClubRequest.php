<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'map_url' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'schedule' => ['sometimes', 'nullable', 'array', 'max:14'],
            'schedule.*' => ['required', 'array:days,hours'],
            'schedule.*.days' => ['required', 'string', 'max:100'],
            'schedule.*.hours' => ['required', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^\+?[0-9][0-9 ()-]{5,31}$/'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{7,15}$/'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._]+$/'],
            'cover' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
