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
            $table->dateTime('activated_at')->nullable();
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
        $response->assertJsonStructure(['data' => ['memberships', 'last_membership', 'message']]);
        $response->assertJsonPath('data.memberships', []);
        $response->assertJsonPath('data.last_membership', null);
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
            ->assertJsonPath('data.memberships.0.status', 'active')
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
            ->assertJsonPath('data.memberships.0.status', 'pending_activation')
            ->assertJsonPath('data.memberships.0.valid_until', null)
            ->assertJsonPath('data.memberships.1.id', (string) $frozen->id)
            ->assertJsonPath('data.memberships.1.active', false)
            ->assertJsonPath('data.memberships.1.status', 'frozen')
            ->assertJsonPath('data.last_membership', null);
    }

    public function test_current_memberships_are_ordered_by_status_then_latest_purchase(): void {
        $client = $this->client();
        $active = $this->program($client);
        $pending = $this->program($client, ['active_until' => null]);
        $frozen = $this->program($client);
        StopCard::create(['service_sale_id' => $frozen->id, 'is_active' => true]);
        $newActive = $this->program($client);
        $this->program($client, ['active_until' => today()->subDay()->toDateTimeString()]);

        $this->memberships($client)->assertOk()->assertJsonCount(4, 'data.memberships')
            ->assertJsonPath('data.memberships.0.id', (string) $newActive->id)
            ->assertJsonPath('data.memberships.1.id', (string) $active->id)
            ->assertJsonPath('data.memberships.2.id', (string) $pending->id)
            ->assertJsonPath('data.memberships.3.id', (string) $frozen->id)
            ->assertJsonPath('data.last_membership', null);
    }

    public function test_latest_expired_purchase_is_shown_when_no_current_memberships_exist(): void {
        $client = $this->client();
        $this->program($client, ['active_until' => today()->subDays(5)->toDateTimeString()]);
        $latest = $this->program($client, ['active_until' => today()->subDay()->toDateTimeString()]);
        $this->memberships($client)->assertOk()->assertJsonPath('data.memberships', [])
            ->assertJsonPath('data.last_membership.id', (string) $latest->id)
            ->assertJsonPath('data.last_membership.status', 'expired')
            ->assertJsonPath('data.last_membership.active', false);
    }

    public function test_exhausted_membership_is_not_usable_even_with_a_negative_balance_or_freeze(): void {
        $client = $this->client();
        $program = $this->program($client, ['entries_count' => 0]);
        SessionService::create(['service_sale_id' => $program->id]);
        StopCard::create(['service_sale_id' => $program->id, 'is_active' => true]);
        $this->memberships($client)->assertOk()->assertJsonPath('data.memberships', [])
            ->assertJsonPath('data.last_membership.status', 'exhausted')
            ->assertJsonPath('data.last_membership.active', false);
    }

    public function test_history_returns_all_owned_membership_states_with_purchase_dates(): void {
        $client = $this->client();
        $activatedAt = now()->subDay();
        $active = $this->program($client, ['activated_at' => $activatedAt->toDateTimeString()]);
        $expired = $this->program($client, ['active_until' => today()->subDay()->toDateTimeString()]);
        $exhausted = $this->program($client, ['entries_count' => 0]);
        $pending = $this->program($client, ['active_until' => null]);
        $frozen = $this->program($client);
        StopCard::create(['service_sale_id' => $frozen->id, 'is_active' => true]);
        $this->program($client, [], Service::TYPE_SOLARIUM);
        $other = $this->client();
        $this->program($other);

        $response = $this->history($client, '?client_id=' . $other->id)->assertOk()->assertJsonCount(5, 'data.items');
        $response->assertJsonPath('data.items.0.id', (string) $frozen->id)
            ->assertJsonPath('data.items.0.status', 'frozen')
            ->assertJsonPath('data.items.1.id', (string) $pending->id)
            ->assertJsonPath('data.items.1.status', 'pending_activation')
            ->assertJsonPath('data.items.1.activated_at', null)
            ->assertJsonPath('data.items.2.id', (string) $exhausted->id)
            ->assertJsonPath('data.items.2.status', 'exhausted')
            ->assertJsonPath('data.items.3.id', (string) $expired->id)
            ->assertJsonPath('data.items.3.status', 'expired')
            ->assertJsonPath('data.items.4.id', (string) $active->id)
            ->assertJsonPath('data.items.4.status', 'active')
            ->assertJsonPath('data.items.4.purchased_at', $active->sale->created_at->toIso8601String())
            ->assertJsonPath('data.items.4.activated_at', $activatedAt->toIso8601String())
            ->assertJsonPath('data.next_cursor', null);
    }

    public function test_deleting_a_catalog_service_does_not_hide_a_purchased_membership(): void {
        $client = $this->client();
        $program = $this->program($client);
        $program->service->delete();
        $this->memberships($client)->assertOk()->assertJsonPath('data.memberships.0.id', (string) $program->id);
        $this->history($client)->assertOk()->assertJsonPath('data.items.0.id', (string) $program->id);
    }

    public function test_history_cursor_is_stable_when_another_purchase_is_added(): void {
        $client = $this->client();
        $first = $this->program($client);
        $second = $this->program($client);
        $this->program($client);
        $this->history($client, '?limit=2&cursor=0')->assertOk()
            ->assertJsonCount(2, 'data.items')->assertJsonPath('data.next_cursor', $second->sale->id);
        $this->program($client);
        $this->history($client, '?limit=2&cursor=' . $second->sale->id)->assertOk()
            ->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.id', (string) $first->id)
            ->assertJsonPath('data.next_cursor', null);
    }

    public function test_history_default_page_size_is_twenty(): void {
        $client = $this->client();
        for ($i = 0; $i < 21; $i++) {
            $this->program($client);
        }
        $this->history($client)->assertOk()->assertJsonCount(20, 'data.items')
            ->assertJsonPath('data.next_cursor', 2);
    }

    public function test_history_cursor_uses_purchase_id_when_membership_ids_differ(): void {
        $client = $this->client();
        $first = $this->program($client);
        DB::table('sales')->where('salable_id', $first->id)->update(['id' => 100]);
        $second = $this->program($client);
        $this->program($client);
        $cursor = $second->sale->id;
        $this->assertNotEquals($second->id, $cursor);
        $this->history($client, '?limit=2')->assertOk()->assertJsonPath('data.next_cursor', $cursor);
        $this->history($client, '?limit=2&cursor=' . $cursor)->assertOk()
            ->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.id', (string) $first->id)
            ->assertJsonPath('data.next_cursor', null);
    }

    public function test_empty_history_and_maximum_page_size(): void {
        $this->history($this->client(), '?limit=50')->assertOk()
            ->assertExactJson(['data' => ['items' => [], 'next_cursor' => null]]);
    }

    /** @dataProvider invalidHistoryPagination */
    public function test_history_rejects_invalid_pagination(string $query): void {
        $this->history($this->client(), '?' . $query)->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function invalidHistoryPagination(): array {
        return [['limit=0'], ['limit=51'], ['limit=abc'], ['cursor=-1'], ['cursor=1.5'], ['cursor=abc']];
    }

    public function test_history_requires_authorization(): void {
        $this->getJson('/api/mobile/v1/me/membership/history')->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
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

    private function history(Client $client, string $query = '') {
        return $this->withToken($client->createToken('mobile')->plainTextToken)
            ->getJson('/api/mobile/v1/me/membership/history' . $query);
    }
}
