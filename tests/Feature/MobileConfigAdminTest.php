<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileConfigAdminTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_13_030000_create_mobile_app_settings_table.php'))->up();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
    }

    public function test_admin_can_read_and_partially_update_singleton_without_an_id(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $this->getJson('/api/v1/mobile/config')->assertOk()->assertJsonMissingPath('data.id');
        $this->patchJson('/api/v1/mobile/config', ['app' => ['store_url' => ['ios' => 'https://apps.apple.com/app/id123']]])
            ->assertOk()->assertJsonPath('data.app.min_version', '1.0.0')->assertJsonPath('data.app.store_url.android', '#')
            ->assertJsonPath('data.app.store_url.ios', 'https://apps.apple.com/app/id123');
        $this->patchJson('/api/v1/mobile/config', ['contacts' => [['title' => 'Telegram', 'url' => 'https://t.me/gladiators']]])
            ->assertOk()->assertJsonCount(1, 'data.contacts')->assertJsonPath('data.contacts.0.title', 'Telegram');
        $this->getJson('/api/mobile/v1/config')->assertOk()->assertJsonCount(1, 'data.contacts')
            ->assertJsonPath('data.app.store_url.ios', 'https://apps.apple.com/app/id123');
        $this->assertDatabaseCount('mobile_app_settings', 1);
    }

    public function test_boss_can_enable_and_disable_maintenance(): void {
        $this->loginStaff(Role::ROLE_BOSS);
        $this->patchJson('/api/v1/mobile/config', ['maintenance' => ['enabled' => true, 'message' => 'До 14:00']])
            ->assertOk()->assertJsonPath('data.maintenance.message', 'До 14:00')->assertJsonPath('data.maintenance.enabled', true);
        $this->patchJson('/api/v1/mobile/config', ['maintenance' => ['enabled' => false]])
            ->assertOk()->assertJsonPath('data.maintenance.message', null)->assertJsonPath('data.maintenance.enabled', false);
    }

    public function test_other_staff_cannot_read_or_change_settings(): void {
        $this->loginStaff(Role::ROLE_TRAINER);
        $this->getJson('/api/v1/mobile/config')->assertForbidden();
        $this->patchJson('/api/v1/mobile/config', ['app' => ['min_version' => '2.0.0']])->assertForbidden();
    }

    public function test_admin_endpoints_require_authentication(): void {
        $this->getJson('/api/v1/mobile/config')->assertUnauthorized();
        $this->patchJson('/api/v1/mobile/config', [])->assertUnauthorized();
    }

    public function test_invalid_settings_and_realtime_changes_are_rejected(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $this->patchJson('/api/v1/mobile/config', [
            'contacts' => [], 'app' => ['min_version' => '1.0', 'store_url' => ['ios' => 'not-a-url']],
            'links' => ['privacy_policy' => 'invalid'], 'realtime' => ['key' => 'new-key'],
            'maintenance' => ['enabled' => true],
        ])->assertUnprocessable()->assertJsonValidationErrors(['contacts', 'app.min_version', 'app.store_url.ios', 'links.privacy_policy', 'maintenance.message', 'realtime']);
        $this->patchJson('/api/v1/mobile/config', ['contacts' => [['title' => '', 'url' => 'invalid']]])
            ->assertUnprocessable()->assertJsonValidationErrors(['contacts.0.title', 'contacts.0.url']);
        $this->getJson('/api/v1/mobile/config')->assertOk()->assertJsonPath('data.app.min_version', '1.0.0');
    }

    public function test_enabled_maintenance_cannot_lose_its_message_and_boolean_stays_boolean(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $this->patchJson('/api/v1/mobile/config', ['maintenance' => ['enabled' => '1', 'message' => 'Техработы']])
            ->assertOk()->assertJsonPath('data.maintenance.enabled', true);
        $this->patchJson('/api/v1/mobile/config', ['maintenance' => ['message' => null]])
            ->assertUnprocessable()->assertJsonValidationErrors('maintenance.message');
    }

    public function test_mobile_client_token_cannot_access_admin_config(): void {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
        $id = DB::table('clients')->insertGetId(['name' => 'Клиент']);
        $this->withToken(Client::findOrFail($id)->createToken('mobile')->plainTextToken);
        $this->getJson('/api/v1/mobile/config')->assertUnauthorized();
        $this->patchJson('/api/v1/mobile/config', ['app' => ['min_version' => '2.0.0']])->assertUnauthorized();
    }

    private function loginStaff(int $role): void {
        $id = DB::table('users')->insertGetId(['name' => 'Сотрудник', 'is_active' => true]);
        DB::table('roles')->insert(['id' => $role, 'name' => 'Роль']);
        DB::table('role_user')->insert(['user_id' => $id, 'role_id' => $role]);
        $this->actingAs(User::findOrFail($id), 'api');
    }
}
