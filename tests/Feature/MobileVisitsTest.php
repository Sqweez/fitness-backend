<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Session;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileVisitsTest extends TestCase
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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
        require_once base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php');
        (new \CreatePersonalAccessTokensTable())->up();
    }

    public function test_no_visits_returns_an_empty_page(): void {
        $this->login();
        $this->getJson('/api/mobile/v1/me/visits')->assertOk()
            ->assertExactJson(['data' => ['items' => [], 'next_cursor' => null]]);
    }

    public function test_visits_are_newest_first_and_include_open_and_finished_sessions(): void {
        $client = $this->login();
        $old = Session::create(['client_id' => $client->id, 'finished_at' => now(), 'created_at' => now()->subDay()]);
        $new = Session::create(['client_id' => $client->id]);
        $this->getJson('/api/mobile/v1/me/visits')->assertOk()->assertExactJson(['data' => [
            'items' => [
                ['id' => (string) $new->id, 'at' => $new->created_at->toIso8601String()],
                ['id' => (string) $old->id, 'at' => $old->created_at->toIso8601String()],
            ],
            'next_cursor' => null,
        ]]);
    }

    public function test_pagination_has_no_repeats_when_a_new_visit_is_added(): void {
        $client = $this->login();
        $first = Session::create(['client_id' => $client->id]);
        $second = Session::create(['client_id' => $client->id]);
        $third = Session::create(['client_id' => $client->id]);
        $this->getJson('/api/mobile/v1/me/visits?limit=2&cursor=0')->assertOk()
            ->assertJsonPath('data.items.0.id', (string) $third->id)
            ->assertJsonPath('data.items.1.id', (string) $second->id)
            ->assertJsonPath('data.next_cursor', $second->id);

        Session::create(['client_id' => $client->id]);
        Auth::forgetGuards();
        $this->getJson('/api/mobile/v1/me/visits?limit=2&cursor=' . $second->id)->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', (string) $first->id)
            ->assertJsonPath('data.next_cursor', null);
    }

    public function test_default_page_size_is_twenty(): void {
        $client = $this->login();
        for ($i = 0; $i < 21; $i++) {
            Session::create(['client_id' => $client->id]);
        }
        $this->getJson('/api/mobile/v1/me/visits')->assertOk()
            ->assertJsonCount(20, 'data.items')->assertJsonPath('data.next_cursor', 2);
    }

    public function test_exact_page_size_does_not_create_an_extra_page(): void {
        $client = $this->login();
        for ($i = 0; $i < 3; $i++) {
            Session::create(['client_id' => $client->id]);
        }
        $this->getJson('/api/mobile/v1/me/visits?limit=3')->assertOk()
            ->assertJsonCount(3, 'data.items')->assertJsonPath('data.next_cursor', null);
    }

    public function test_page_size_fifty_is_allowed(): void {
        $this->login();
        $this->getJson('/api/mobile/v1/me/visits?limit=50')->assertOk()
            ->assertExactJson(['data' => ['items' => [], 'next_cursor' => null]]);
    }

    public function test_another_clients_visits_are_not_returned(): void {
        $this->login();
        $other = Client::create(['name' => 'Петров Иван']);
        Session::create(['client_id' => $other->id]);
        $this->getJson('/api/mobile/v1/me/visits?client_id=' . $other->id)->assertOk()
            ->assertExactJson(['data' => ['items' => [], 'next_cursor' => null]]);
    }

    /** @dataProvider invalidPagination */
    public function test_invalid_pagination_is_rejected(string $query): void {
        $this->login();
        $this->getJson('/api/mobile/v1/me/visits?' . $query)
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function invalidPagination(): array {
        return [['limit=0'], ['limit=51'], ['limit=abc'], ['cursor=-1'], ['cursor=1.5'], ['cursor=abc']];
    }

    public function test_visits_require_authorization(): void {
        $this->getJson('/api/mobile/v1/me/visits')->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    private function login(): Client {
        $client = Client::create(['name' => 'Иванов Алексей']);
        $this->withToken($client->createToken('mobile')->plainTextToken);
        return $client;
    }
}
