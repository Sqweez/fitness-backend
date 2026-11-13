<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileConfigTest extends TestCase
{
    private array $settings;

    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'https://socket.example.com', 'reverb.apps.apps.0.key' => 'public-key',
            'reverb.apps.apps.0.secret' => 'private-secret', 'reverb.apps.apps.0.options.host' => 'reverb',
            'reverb.apps.apps.0.options.port' => 8080]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_13_030000_create_mobile_app_settings_table.php'))->up();
        $this->settings = [
            'contacts' => [
                ['title' => 'Позвонить', 'url' => 'tel:+77758761111'],
                ['title' => 'Telegram', 'url' => 'https://t.me/gladiators'],
            ],
            'app' => [
                'min_version' => '1.0.0',
                'store_url' => ['ios' => 'https://apps.apple.com/app/id123', 'android' => 'https://play.google.com/store/apps/details?id=example.app'],
            ],
            'maintenance' => ['enabled' => false, 'message' => 'Скрытый текст'],
            'links' => ['privacy_policy' => 'https://example.com/privacy'],
        ];
        DB::table('mobile_app_settings')->where('id', 1)->update(array_map('json_encode', $this->settings));
    }

    public function test_config_is_public_and_returns_only_the_documented_fields(): void {
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertExactJson(['data' => [
            'contacts' => $this->settings['contacts'],
            'app' => $this->settings['app'],
            'maintenance' => ['enabled' => false, 'message' => null],
            'links' => $this->settings['links'],
            'realtime' => ['key' => 'public-key', 'host' => 'socket.example.com', 'port' => 443, 'scheme' => 'https'],
        ]])->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_maintenance_does_not_prevent_loading_config_and_changes_are_not_cached(): void {
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertJsonPath('data.maintenance.enabled', false);
        DB::table('mobile_app_settings')->where('id', 1)->update([
            'maintenance' => json_encode(['enabled' => true, 'message' => 'Техработы до 14:00']),
            'app' => json_encode(array_replace($this->settings['app'], ['min_version' => '2.0.0'])),
        ]);
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertJsonPath('data.maintenance.enabled', true)
            ->assertJsonPath('data.maintenance.message', 'Техработы до 14:00')->assertJsonPath('data.app.min_version', '2.0.0');
    }

    public function test_public_config_remains_accessible_with_an_invalid_token(): void {
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
        $this->withToken('expired-token')->getJson('/api/mobile/v1/config')->assertOk();
    }

    public function test_unpublished_store_and_privacy_links_are_returned_as_configured(): void {
        DB::table('mobile_app_settings')->where('id', 1)->update([
            'app' => json_encode(['min_version' => '1.0.0', 'store_url' => ['ios' => '#', 'android' => '#']]),
            'links' => json_encode(['privacy_policy' => '#']),
        ]);
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertJsonPath('data.app.store_url.ios', '#')
            ->assertJsonPath('data.app.store_url.android', '#')->assertJsonPath('data.links.privacy_policy', '#');
    }

    public function test_realtime_uses_the_existing_reverb_key_and_public_api_origin(): void {
        config(['app.url' => 'http://localhost:6001/api', 'reverb.apps.apps.0.key' => 'changed-public-key']);
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertJsonPath('data.realtime', [
            'key' => 'changed-public-key', 'host' => 'localhost', 'port' => 6001, 'scheme' => 'http',
        ]);
    }

    public function test_migration_creates_initial_settings_and_can_be_rolled_back(): void {
        $migration = require database_path('migrations/2026_11_13_030000_create_mobile_app_settings_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('mobile_app_settings'));
        $migration->up();
        $this->assertDatabaseCount('mobile_app_settings', 1);
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertJsonCount(2, 'data.contacts')
            ->assertJsonPath('data.app.min_version', '1.0.0')->assertJsonPath('data.app.store_url.ios', '#')
            ->assertJsonPath('data.links.privacy_policy', '#')->assertJsonPath('data.maintenance.enabled', false)
            ->assertJsonPath('data.maintenance.message', null);
    }

}
