<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class StaffAuthTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('club_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        foreach (['role_user' => 'role_id', 'club_user' => 'club_id'] as $name => $column) {
            Schema::create($name, function (Blueprint $table) use ($column) {
                $table->integer('user_id');
                $table->integer($column);
            });
        }
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->morphs('passable');
            $table->softDeletes();
            $table->timestamps();
        });
        require_once database_path('migrations/2022_08_06_193816_create_media_table.php');
        (new \CreateMediaTable())->up();
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
    }

    public function test_password_login_issues_a_sanctum_token_and_preserves_user_roles(): void {
        $user = $this->staff();
        Role::create(['id' => Role::ROLE_ADMIN, 'name' => 'Администратор']);
        $user->roles()->attach(Role::ROLE_ADMIN);
        $response = $this->postJson('/api/auth/login', ['login' => '+7 (777) 123-45-67', 'password' => 'password'])->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $token = $response->json('access_token');
        $this->assertSame($user->id, (int) PersonalAccessToken::findToken($token)->tokenable_id);
        $response->assertJsonPath('token_type', 'bearer')->assertJsonPath('expires_in', null);
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertOk()
            ->assertJsonPath('id', $user->id)->assertJsonPath('roles.0.id', Role::ROLE_ADMIN);
    }

    public function test_card_login_issues_a_staff_token(): void {
        $user = $this->staff();
        $user->pass()->create(['code' => '000123']);
        $response = $this->postJson('/api/auth/login', ['pass' => '000123'])->assertOk();
        $this->assertSame(User::class, PersonalAccessToken::findToken($response->json('access_token'))->tokenable_type);
    }

    public function test_invalid_credentials_and_cards_do_not_issue_tokens(): void {
        $this->staff();
        $this->postJson('/api/auth/login', ['login' => '+77771234567', 'password' => 'wrong'])->assertStatus(403);
        $this->postJson('/api/auth/login', ['login' => '+77770000000', 'password' => 'password'])->assertStatus(403);
        $this->postJson('/api/auth/login', ['pass' => 'missing'])->assertStatus(403);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_and_deleted_staff_cannot_login_by_password_or_card(): void {
        $user = $this->staff(['is_active' => false]);
        $user->pass()->create(['code' => '000123']);
        $this->postJson('/api/auth/login', ['login' => $user->phone, 'password' => 'password'])->assertStatus(403);
        $this->postJson('/api/auth/login', ['pass' => '000123'])->assertStatus(403);
        $user->update(['is_active' => true]);
        $user->delete();
        $this->postJson('/api/auth/login', ['login' => $user->phone, 'password' => 'password'])->assertStatus(403);
        $this->postJson('/api/auth/login', ['pass' => '000123'])->assertStatus(403);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_only_the_current_staff_token(): void {
        $user = $this->staff();
        $first = $user->createToken('dashboard')->plainTextToken;
        $second = $user->createToken('dashboard')->plainTextToken;
        $this->withToken($first)->postJson('/api/auth/logout')->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($first));
        $this->assertNotNull(PersonalAccessToken::findToken($second));
        Auth::forgetGuards();
        $this->withToken($first)->getJson('/api/auth/me')->assertStatus(401);
        Auth::forgetGuards();
        $this->withToken($second)->getJson('/api/auth/me')->assertOk();
    }

    public function test_client_and_staff_tokens_cannot_cross_guards(): void {
        $user = $this->staff();
        $staffToken = $user->createToken('dashboard')->plainTextToken;
        $client = Client::create(['name' => 'Клиент']);
        $clientToken = $client->createToken('mobile')->plainTextToken;
        $this->withToken($clientToken)->getJson('/api/auth/me')->assertStatus(401);
        Auth::forgetGuards();
        $this->withToken($clientToken)->postJson('/api/auth/logout')->assertStatus(401);
        $this->assertNotNull(PersonalAccessToken::findToken($clientToken));
        Auth::forgetGuards();
        $this->withToken($staffToken)->getJson('/api/mobile/v1/me')->assertStatus(401);
    }

    public function test_disabled_staff_cannot_keep_using_an_existing_token(): void {
        $user = $this->staff();
        $token = $user->createToken('dashboard')->plainTextToken;
        $user->update(['is_active' => false]);
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_staff_token_does_not_expire_while_mobile_tokens_remain_unchanged(): void {
        $user = $this->staff();
        $token = $user->createToken('dashboard');
        $token->accessToken->update(['created_at' => now()->subYear()]);
        $this->withToken($token->plainTextToken)->getJson('/api/auth/me')->assertOk();
    }

    public function test_me_and_logout_require_authentication_and_refresh_is_removed(): void {
        $this->getJson('/api/auth/me')->assertStatus(401);
        $this->postJson('/api/auth/logout')->assertStatus(401);
        $this->getJson('/api/auth/refresh')->assertStatus(404);
    }

    /** @dataProvider invalidLogins */
    public function test_login_validates_input(array $data): void {
        $this->postJson('/api/auth/login', $data)->assertStatus(422);
    }

    public static function invalidLogins(): array {
        return [[[]], [['login' => '+77771234567']], [['password' => 'password']], [['pass' => []]], [['login' => [], 'password' => 'password']]];
    }

    private function staff(array $attributes = []): User {
        return User::create(array_merge(['name' => 'Администратор', 'phone' => '+77771234567', 'password' => Hash::make('password')], $attributes));
    }
}
