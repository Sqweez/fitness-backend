<?php

namespace App\Http\Services;

use App\Models\MobileAppSetting;
use Illuminate\Support\Facades\DB;

class MobileConfigService
{
    public function updateConfig(array $data): array {
        return DB::transaction(function () use ($data) {
            $settings = MobileAppSetting::query()->lockForUpdate()->findOrFail(1);
            foreach ($data as $section => $value) {
                $settings->{$section} = $section === 'contacts' ? $value : array_replace_recursive($settings->{$section}, $value);
            }
            $settings->maintenance = array_replace($settings->maintenance, ['enabled' => (bool) $settings->maintenance['enabled']]);
            $settings->save();
            return $this->getConfig();
        });
    }

    public function getConfig(): array {
        $settings = MobileAppSetting::findOrFail(1);
        $url = parse_url(config('app.url'));

        return [
            'contacts' => $settings->contacts,
            'app' => $settings->app,
            'maintenance' => [
                'enabled' => $settings->maintenance['enabled'],
                'message' => $settings->maintenance['enabled'] ? $settings->maintenance['message'] : null,
            ],
            'links' => $settings->links,
            'realtime' => [
                'key' => config('reverb.apps.apps.0.key'),
                'host' => $url['host'],
                'port' => $url['port'] ?? ($url['scheme'] === 'https' ? 443 : 80),
                'scheme' => $url['scheme'],
            ],
        ];
    }
}
