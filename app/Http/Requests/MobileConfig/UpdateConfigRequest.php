<?php

namespace App\Http\Requests\MobileConfig;

use App\Models\MobileAppSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateConfigRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'contacts' => ['sometimes', 'required', 'array', 'list', 'min:1'],
            'contacts.*' => ['required', 'array:title,url'],
            'contacts.*.title' => ['required', 'string', 'max:255'],
            'contacts.*.url' => ['required', 'string', 'max:2048', 'regex:~^[a-z][a-z0-9+.-]*:\S+$~i'],
            'app' => ['sometimes', 'required', 'array:min_version,store_url', 'min:1'],
            'app.min_version' => ['sometimes', 'required', 'string', 'max:32', 'regex:/^\d+\.\d+\.\d+$/'],
            'app.store_url' => ['sometimes', 'required', 'array:ios,android', 'min:1'],
            'app.store_url.ios' => ['sometimes', 'required', 'string', 'max:2048', 'regex:~^(?:#|https?://\S+)$~'],
            'app.store_url.android' => ['sometimes', 'required', 'string', 'max:2048', 'regex:~^(?:#|https?://\S+)$~'],
            'maintenance' => ['sometimes', 'required', 'array:enabled,message', 'min:1'],
            'maintenance.enabled' => ['sometimes', 'required', 'boolean'],
            'maintenance.message' => ['required_if:maintenance.enabled,true', 'nullable', 'string', 'max:2000'],
            'links' => ['sometimes', 'required', 'array:privacy_policy', 'min:1'],
            'links.privacy_policy' => ['sometimes', 'required', 'string', 'max:2048', 'regex:~^(?:#|https?://\S+)$~'],
            'realtime' => ['prohibited'],
        ];
    }

    public function after(): array {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || !$this->has('maintenance')) {
                return;
            }
            $maintenance = array_replace(MobileAppSetting::findOrFail(1)->maintenance, $this->input('maintenance'));
            if ($maintenance['enabled'] && blank($maintenance['message'])) {
                $validator->errors()->add('maintenance.message', 'Укажите сообщение о технических работах.');
            }
        }];
    }
}
