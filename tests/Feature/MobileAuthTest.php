<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Cache::flush();
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->softDeletes();
            $table->timestamps();
        });
        require_once base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php');
        (new \CreatePersonalAccessTokensTable())->up();
        Route::get('api/mobile/v1/testing/me', function () {
            return ['id' => (string) auth('mobile')->id()];
        })->middleware('auth:mobile');
    }

    public function test_code_request_returns_metadata_without_sending_a_message(): void {
        $this->client();
        $this->postJson('/api/mobile/v1/auth/code', ['phone' => '+77011234567'])
            ->assertOk()->assertExactJson(['code_length' => 6, 'retry_after_seconds' => 60]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unknown_phone_is_rejected_by_both_endpoints(): void {
        foreach (['code', 'verify'] as $endpoint) {
            $this->postJson('/api/mobile/v1/auth/' . $endpoint, ['phone' => '+77011234567', 'code' => '111111'])
                ->assertStatus(404)->assertJsonPath('error.code', 'PHONE_NOT_FOUND');
        }
    }

    public function test_duplicate_phones_are_rejected_after_normalization(): void {
        $this->client('+77011234567');
        $this->client('8 (701) 123-45-67');
        foreach (['code', 'verify'] as $endpoint) {
            $this->postJson('/api/mobile/v1/auth/' . $endpoint, ['phone' => '+77011234567', 'code' => '111111'])
                ->assertStatus(409)->assertJsonPath('error.code', 'PHONE_AMBIGUOUS');
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_deleted_duplicate_does_not_prevent_login(): void {
        $client = $this->client();
        $this->client()->delete();
        $this->requestCode();
        $token = $this->verify()->assertOk()->json('token');
        $this->assertSame($client->id, (int) PersonalAccessToken::findToken($token)->tokenable_id);
    }

    public function test_correct_code_issues_a_client_token_which_does_not_expire(): void {
        $client = $this->client('77011234567');
        $this->requestCode();
        $token = $this->verify()->assertOk()->assertJsonStructure(['token'])->json('token');
        $this->assertNotSame('111111', $token);
        $this->assertSame(Client::class, PersonalAccessToken::findToken($token)->tokenable_type);
        $this->travel(366)->days();
        $this->withToken($token)->getJson('/api/mobile/v1/testing/me')
            ->assertOk()->assertJsonPath('id', (string) $client->id);
    }

    public function test_wrong_code_does_not_issue_a_token(): void {
        $this->client();
        $this->requestCode();
        $this->verify('123456')->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CODE');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_verification_requires_an_unexpired_code_request(): void {
        $this->client();
        $this->verify()->assertStatus(422)->assertJsonPath('error.code', 'CODE_EXPIRED');
        $this->requestCode();
        $this->travel(301)->seconds();
        $this->verify()->assertStatus(422)->assertJsonPath('error.code', 'CODE_EXPIRED');
    }

    public function test_successful_code_cannot_be_reused(): void {
        $this->client();
        $this->requestCode();
        $this->verify()->assertOk();
        $this->verify()->assertStatus(422)->assertJsonPath('error.code', 'CODE_EXPIRED');
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_code_is_consumed_before_token_creation_if_lock_lease_ends(): void {
        $this->client();
        $this->requestCode();
        $creating = false;
        PersonalAccessToken::creating(function () use (&$creating) {
            if ($creating) {
                return;
            }
            $creating = true;
            Cache::lock('mobile:auth:lock:+77011234567', 10)->forceRelease();
            $this->verify()->assertStatus(422)->assertJsonPath('error.code', 'CODE_EXPIRED');
        });

        $this->verify()->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_five_wrong_attempts_block_even_the_correct_code(): void {
        $this->client();
        $this->requestCode();
        for ($i = 0; $i < 5; $i++) {
            $this->verify('123456')->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CODE');
        }
        $this->verify()->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_code_requests_have_a_cooldown_and_hourly_limit(): void {
        $this->client();
        $this->requestCode()->assertOk();
        $this->requestCode()->assertStatus(429)->assertJsonPath('error.retry_after_seconds', 60);
        for ($i = 0; $i < 2; $i++) {
            $this->travel(61)->seconds();
            $this->requestCode()->assertOk();
        }
        $this->travel(61)->seconds();
        $this->requestCode()->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_code_requests_have_a_daily_limit(): void {
        $this->client();
        for ($i = 0; $i < 5; $i++) {
            $this->requestCode()->assertOk();
            $this->travel(61)->minutes();
        }
        $this->requestCode()->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_resending_does_not_reset_wrong_code_attempts(): void {
        $this->client();
        $this->requestCode();
        for ($i = 0; $i < 5; $i++) {
            $this->verify('123456')->assertStatus(422);
        }
        $this->travel(61)->seconds();
        $this->requestCode()->assertOk();
        $this->verify()->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_global_ip_throttle_uses_mobile_error_format(): void {
        for ($i = 0; $i < 60; $i++) {
            $this->requestCode()->assertStatus(404);
        }
        $this->requestCode()->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED')
            ->assertJsonStructure(['error' => ['retry_after_seconds']]);
    }

    public function test_deleting_a_client_invalidates_access_with_its_token(): void {
        $client = $this->client();
        $this->requestCode();
        $token = $this->verify()->assertOk()->json('token');
        $client->delete();
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/mobile/v1/testing/me')
            ->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_duplicate_added_after_sending_prevents_verification(): void {
        $this->client();
        $this->requestCode();
        $this->client();
        $this->verify()->assertStatus(409)->assertJsonPath('error.code', 'PHONE_AMBIGUOUS');
    }

    public function test_changing_phone_owner_does_not_transfer_the_code(): void {
        $client = $this->client();
        $this->requestCode();
        $client->delete();
        $this->client();
        $this->verify()->assertStatus(422)->assertJsonPath('error.code', 'CODE_EXPIRED');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_input_uses_mobile_error_format(): void {
        $this->postJson('/api/mobile/v1/auth/code', ['phone' => 'not-a-phone'])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->postJson('/api/mobile/v1/auth/verify', ['phone' => '+77011234567', 'code' => '1'])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_logout_requires_a_mobile_token(): void {
        $this->postJson('/api/mobile/v1/auth/logout')
            ->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_logout_revokes_only_the_current_token(): void {
        $client = $this->client();
        $this->requestCode();
        $token = $this->verify()->assertOk()->json('token');
        $otherToken = $client->createToken('other-device')->plainTextToken;
        $this->withToken($token)->postJson('/api/mobile/v1/auth/logout')->assertNoContent();
        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertNotNull(PersonalAccessToken::findToken($otherToken));
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/mobile/v1/testing/me')->assertStatus(401);
    }

    public function test_mobile_token_cannot_authenticate_dashboard_requests(): void {
        $this->client();
        $this->requestCode();
        $token = $this->verify()->assertOk()->json('token');
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/clients')->assertStatus(401);
    }

    public function test_docs_openapi_yaml_is_served_publicly(): void {
        $response = $this->get('/api/mobile/v1/docs/openapi.yaml');
        $response->assertOk();
        $this->assertStringContainsString('text/yaml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Gladiators Mobile API', $response->getContent());
    }

    public function test_docs_swagger_ui_is_served_publicly(): void {
        $response = $this->get('/api/mobile/v1/docs');
        $response->assertOk();
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('swagger-ui', $response->getContent());
    }

    private function client(string $phone = '+77011234567'): Client {
        return Client::create(['name' => 'Иванов Алексей Иванович', 'phone' => $phone]);
    }

    private function requestCode() {
        return $this->postJson('/api/mobile/v1/auth/code', ['phone' => '+77011234567']);
    }

    private function verify(string $code = '111111') {
        return $this->postJson('/api/mobile/v1/auth/verify', ['phone' => '+77011234567', 'code' => $code]);
    }
}
