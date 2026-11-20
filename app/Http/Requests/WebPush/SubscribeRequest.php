<?php

namespace App\Http\Requests\WebPush;

use App\Models\WebPushSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'endpoint' => ['required', 'string', 'url:https', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['required', 'string', Rule::in(WebPushSubscription::CONTENT_ENCODINGS)],
        ];
    }
}
