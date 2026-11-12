<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileProfileTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        require_once base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php');
        (new \CreatePersonalAccessTokensTable())->up();
    }

    /** @dataProvider names */
    public function test_profile_returns_first_and_last_name(string $name, array $expected): void {
        $client = Client::create(['name' => $name]);
        $token = $client->createToken('mobile')->plainTextToken;
        $this->withToken($token)->getJson('/api/mobile/v1/me')->assertOk()->assertExactJson(['data' => $expected]);
    }

    public function names(): array {
        return [
            ['Иванов Алексей Иванович', ['first_name' => 'Алексей', 'last_name' => 'Иванов']],
            ["  Қайратов   Әли\tНұрланұлы  ", ['first_name' => 'Әли', 'last_name' => 'Қайратов']],
            ['Иванов Алексей', ['first_name' => 'Алексей', 'last_name' => 'Иванов']],
        ];
    }

    public function test_profile_requires_a_valid_mobile_token(): void {
        $this->getJson('/api/mobile/v1/me')->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
        $this->withToken('invalid-token')->getJson('/api/mobile/v1/me')
            ->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_profile_uses_token_owner_instead_of_requested_client_id(): void {
        $client = Client::create(['name' => 'Иванов Алексей Иванович']);
        $otherClient = Client::create(['name' => 'Петров Иван Петрович']);
        $token = $client->createToken('mobile')->plainTextToken;
        $this->withToken($token)->getJson('/api/mobile/v1/me?client_id=' . $otherClient->id)
            ->assertOk()->assertExactJson(['data' => ['first_name' => 'Алексей', 'last_name' => 'Иванов']]);
    }
}
