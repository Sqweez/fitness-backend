<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pass;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePassTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cached_pass')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->nullableMorphs('passable');
            $table->timestamps();
        });
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
    }

    /** @dataProvider codes */
    public function test_assigned_card_returns_its_original_code_without_a_membership(string $code): void {
        $client = $this->client();
        $client->pass()->create(['code' => $code]);
        $this->pass($client)->assertOk()->assertExactJson(['data' => ['code' => $code, 'format' => 'CODE128']]);
    }

    public static function codes(): array {
        return [['0006287938'], ['6287938'], ['CLUB-123']];
    }

    public function test_missing_card_is_unavailable_even_with_a_cached_code(): void {
        $client = $this->client();
        $client->update(['cached_pass' => '6287938']);
        $this->pass($client)->assertStatus(403)->assertJsonPath('error.code', 'PASS_UNAVAILABLE');
    }

    public function test_another_clients_card_cannot_be_requested(): void {
        $client = $this->client();
        $other = $this->client();
        $other->pass()->create(['code' => '6287938']);
        $this->pass($client, '?client_id=' . $other->id)
            ->assertStatus(403)->assertJsonPath('error.code', 'PASS_UNAVAILABLE');
    }

    public function test_staff_card_with_the_same_id_is_not_a_client_card(): void {
        $client = $this->client();
        Pass::create(['code' => '6287938', 'passable_id' => $client->id, 'passable_type' => User::class]);
        $this->pass($client)->assertStatus(403)->assertJsonPath('error.code', 'PASS_UNAVAILABLE');
    }

    public function test_detached_card_is_not_returned(): void {
        $client = $this->client();
        $card = $client->pass()->create(['code' => '6287938']);
        $card->update(['passable_id' => null, 'passable_type' => null]);
        $this->pass($client)->assertStatus(403)->assertJsonPath('error.code', 'PASS_UNAVAILABLE');
    }

    public function test_pass_requires_authorization(): void {
        $this->getJson('/api/mobile/v1/me/pass')->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    private function client(): Client {
        return Client::create(['name' => 'Иванов Алексей']);
    }

    private function pass(Client $client, string $query = '') {
        return $this->withToken($client->createToken('mobile')->plainTextToken)
            ->getJson('/api/mobile/v1/me/pass' . $query);
    }
}
