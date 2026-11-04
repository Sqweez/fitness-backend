<?php

namespace App\Http\Requests\Economy;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class GetEconomyReportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start' => 'required|date',
            'finish' => 'required|date|after_or_equal:start',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:200',
            'search' => 'nullable|string|max:100',
            'club_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
        ];
    }

    public function filters(): array
    {
        return [
            'start' => Carbon::parse($this->input('start'))->startOfDay(),
            'finish' => Carbon::parse($this->input('finish'))->endOfDay(),
            'page' => (int) $this->input('page', 1),
            'per_page' => (int) $this->input('per_page', 50),
            'search' => $this->input('search'),
            'club_id' => $this->input('club_id'),
            'user_id' => $this->input('user_id'),
        ];
    }
}
