<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Session;
use App\Models\Trinket;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileTrinketTest extends TestCase
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
        Schema::create('trinkets', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('cabinet_number');
            $table->timestamps();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->integer('trinket_id')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
    }

    public function test_active_session_returns_cabinet_number_instead_of_key_code(): void {
        $client = $this->client();
        $trinket = Trinket::create(['code' => '123456', 'cabinet_number' => '017-А']);
        Session::create(['client_id' => $client->id, 'trinket_id' => $trinket->id]);
        $this->trinket($client)->assertOk()->assertExactJson(['data' => ['number' => '017-А']]);
    }

    public function test_client_without_visits_has_no_key(): void {
        $this->trinket($this->client())->assertOk()->assertExactJson(['data' => null]);
    }

    public function test_finished_session_does_not_return_its_key(): void {
        $client = $this->client();
        $trinket = Trinket::create(['code' => '123456', 'cabinet_number' => '17']);
        Session::create(['client_id' => $client->id, 'trinket_id' => $trinket->id, 'finished_at' => now()]);
        $this->trinket($client)->assertOk()->assertExactJson(['data' => null]);
    }

    public function test_open_session_without_key_does_not_use_previous_sessions_key(): void {
        $client = $this->client();
        $trinket = Trinket::create(['code' => '123456', 'cabinet_number' => '17']);
        Session::create(['client_id' => $client->id, 'trinket_id' => $trinket->id, 'finished_at' => now()]);
        Session::create(['client_id' => $client->id]);
        $this->trinket($client)->assertOk()->assertExactJson(['data' => null]);
    }

    public function test_another_clients_key_cannot_be_requested(): void {
        $client = $this->client();
        $other = $this->client();
        $trinket = Trinket::create(['code' => '123456', 'cabinet_number' => '17']);
        Session::create(['client_id' => $other->id, 'trinket_id' => $trinket->id]);
        $this->trinket($client, '?client_id=' . $other->id)->assertOk()->assertExactJson(['data' => null]);
    }

    public function test_trinket_requires_authorization(): void {
        $this->getJson('/api/mobile/v1/me/trinket')
            ->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    private function client(): Client {
        return Client::create(['name' => 'Иванов Алексей']);
    }

    private function trinket(Client $client, string $query = '') {
        return $this->withToken($client->createToken('mobile')->plainTextToken)
            ->getJson('/api/mobile/v1/me/trinket' . $query);
    }
}
