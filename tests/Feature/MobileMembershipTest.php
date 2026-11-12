<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientServicePenalty;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\SessionService;
use App\Models\StopCard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileMembershipTest extends TestCase
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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('service_type_id');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('service_sale', function (Blueprint $table) {
            $table->id();
            $table->integer('service_id');
            $table->integer('entries_count')->nullable();
            $table->string('self_name')->nullable();
            $table->dateTime('active_until')->nullable();
            $table->timestamps();
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->morphs('salable');
            $table->timestamps();
        });
        Schema::create('session_services', function (Blueprint $table) {
            $table->id();
            $table->integer('service_sale_id');
            $table->timestamps();
        });
        Schema::create('client_service_penalties', function (Blueprint $table) {
            $table->id();
            $table->integer('service_sale_id');
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_declined')->default(false);
            $table->timestamps();
        });
        Schema::create('stop_cards', function (Blueprint $table) {
            $table->id();
            $table->integer('service_sale_id');
            $table->boolean('is_active');
            $table->dateTime('unstopped_at')->nullable();
            $table->timestamps();
        });
        require_once base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php');
        (new \CreatePersonalAccessTokensTable())->up();
    }

    public function test_no_memberships_returns_an_empty_list_and_message(): void {
        $response = $this->memberships($this->client())->assertOk();
        $response->assertJsonPath('data.memberships', []);
        $this->assertNotEmpty($response->json('data.message'));
    }

    public function test_multiple_current_memberships_have_individual_balances_and_statuses(): void {
        $client = $this->client();
        $limited = $this->program($client, ['self_name' => 'Персональный тариф']);
        SessionService::create(['service_sale_id' => $limited->id]);
        $unlimited = $this->program($client, ['entries_count' => null], Service::TYPE_UNLIMITED);

        $this->memberships($client)->assertOk()->assertJsonCount(2, 'data.memberships')
            ->assertJsonPath('data.memberships.0.id', (string) $unlimited->id)
            ->assertJsonPath('data.memberships.0.active', true)
            ->assertJsonPath('data.memberships.0.limited', false)
            ->assertJsonPath('data.memberships.0.visits_left', null)
            ->assertJsonPath('data.memberships.0.visits_total', null)
            ->assertJsonPath('data.memberships.1.id', (string) $limited->id)
            ->assertJsonPath('data.memberships.1.title', 'Персональный тариф')
            ->assertJsonPath('data.memberships.1.active', true)
            ->assertJsonPath('data.memberships.1.limited', true)
            ->assertJsonPath('data.memberships.1.visits_left', 3)
            ->assertJsonPath('data.memberships.1.visits_total', 4)
            ->assertJsonPath('data.memberships.1.valid_until', now()->addDays(10)->endOfDay()->toIso8601String());
    }

    public function test_expired_exhausted_solarium_and_other_clients_programs_are_excluded(): void {
        $client = $this->client();
        $this->program($client, ['active_until' => today()->subDay()->toDateTimeString()]);
        $exhausted = $this->program($client, ['entries_count' => 1]);
        SessionService::create(['service_sale_id' => $exhausted->id]);
        $overused = $this->program($client, ['entries_count' => 0]);
        SessionService::create(['service_sale_id' => $overused->id]);
        $this->program($client, [], Service::TYPE_SOLARIUM);
        $this->program($this->client());
        $this->memberships($client)->assertOk()->assertJsonPath('data.memberships', []);
    }

    public function test_frozen_and_unactivated_memberships_remain_in_the_current_list(): void {
        $client = $this->client();
        $frozen = $this->program($client, ['active_until' => today()->subDay()->toDateTimeString()]);
        StopCard::create(['service_sale_id' => $frozen->id, 'is_active' => true]);
        $unactivated = $this->program($client, ['active_until' => null]);
        $this->memberships($client)->assertOk()->assertJsonCount(2, 'data.memberships')
            ->assertJsonPath('data.memberships.0.id', (string) $unactivated->id)
            ->assertJsonPath('data.memberships.0.active', false)
            ->assertJsonPath('data.memberships.0.valid_until', null)
            ->assertJsonPath('data.memberships.1.id', (string) $frozen->id)
            ->assertJsonPath('data.memberships.1.active', false);
    }

    public function test_only_confirmed_penalties_reduce_remaining_visits(): void {
        $client = $this->client();
        $program = $this->program($client);
        ClientServicePenalty::create(['service_sale_id' => $program->id, 'is_accepted' => true]);
        ClientServicePenalty::create(['service_sale_id' => $program->id]);
        ClientServicePenalty::create(['service_sale_id' => $program->id, 'is_declined' => true]);
        $this->memberships($client)->assertOk()->assertJsonPath('data.memberships.0.visits_left', 3);
    }

    public function test_membership_is_available_through_the_last_calendar_day(): void {
        $client = $this->client();
        $this->program($client, ['active_until' => today()->toDateTimeString()]);
        $this->memberships($client)->assertOk()->assertJsonPath('data.memberships.0.active', true)
            ->assertJsonPath('data.memberships.0.valid_until', today()->endOfDay()->toIso8601String());
    }

    public function test_memberships_require_authorization(): void {
        $this->getJson('/api/mobile/v1/me/membership')
            ->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    private function client(): Client {
        return Client::create(['name' => 'Иванов Алексей']);
    }

    private function program(Client $client, array $attributes = [], int $type = Service::TYPE_PROGRAM): ServiceSale {
        $service = Service::create(['name' => 'Тренажёрный зал', 'service_type_id' => $type]);
        $program = ServiceSale::create(array_merge([
            'service_id' => $service->id,
            'entries_count' => 4,
            'active_until' => now()->addDays(10)->toDateTimeString(),
        ], $attributes));
        $program->sale()->create(['client_id' => $client->id]);
        return $program;
    }

    private function memberships(Client $client) {
        return $this->withToken($client->createToken('mobile')->plainTextToken)
            ->getJson('/api/mobile/v1/me/membership');
    }
}
