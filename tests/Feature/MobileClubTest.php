<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Club;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use Tests\TestCase;

#[RunClassInSeparateProcess]
#[PreserveGlobalState(false)]
class MobileClubTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'media-library.disk_name' => 'public']);
        DB::purge('sqlite');
        Storage::fake('public');
        require_once database_path('migrations/2022_06_27_223133_create_clubs_table.php');
        (new \CreateClubsTable())->up();
        (require database_path('migrations/2026_11_13_020000_create_club_profiles_table.php'))->up();
        foreach (['clients', 'users'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('club_id');
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        foreach (['role_user' => 'role_id', 'club_user' => 'club_id'] as $name => $column) {
            Schema::create($name, function (Blueprint $table) use ($column) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger($column);
            });
        }
        require_once database_path('migrations/2022_08_06_193816_create_media_table.php');
        (new \CreateMediaTable())->up();
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
    }

    public function test_club_requires_mobile_authentication(): void {
        $this->getJson('/api/mobile/v1/club')->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_club_is_resolved_from_client_and_cannot_be_overridden(): void {
        $first = Club::create(['name' => 'Первый клуб']);
        $second = Club::create(['name' => 'Второй клуб']);
        $client = Client::create(['name' => 'Иванов Иван', 'club_id' => $first->id]);
        $this->withToken($client->createToken('mobile')->plainTextToken);
        $this->getJson('/api/mobile/v1/club?club_id=' . $second->id)->assertOk()
            ->assertJsonPath('data.id', (string) $first->id)->assertJsonPath('data.name', 'Первый клуб');
        $client->update(['club_id' => $second->id]);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/mobile/v1/club')->assertOk()->assertJsonPath('data.id', (string) $second->id);
    }

    public function test_admin_can_update_only_an_accessible_club(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $other = Club::create(['name' => 'Другой клуб']);
        $this->loginStaff($club, Role::ROLE_ADMIN);
        $this->patchJson('/api/v1/clubs/' . $club->id, $this->payload())->assertOk()
            ->assertJsonPath('data.address', 'Павлодар, ул. Спортивная, 1');
        $this->getJson('/api/v1/clubs/' . $club->id)->assertOk()->assertJsonPath('data.schedule', $this->payload()['schedule']);
        $this->patchJson('/api/v1/clubs/' . $other->id, $this->payload())->assertForbidden();
        $this->getJson('/api/v1/clubs/' . $other->id)->assertForbidden();
        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'name' => 'Гладиаторы']);
        $this->assertDatabaseHas('club_profiles', ['club_id' => $club->id, 'whatsapp' => '77758761111']);
    }

    public function test_boss_can_manage_club_but_other_roles_cannot(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $this->loginStaff($club, Role::ROLE_BOSS);
        $this->patchJson('/api/v1/clubs/' . $club->id, $this->payload())->assertOk();
        $this->loginStaff($club, Role::ROLE_TRAINER);
        $this->patchJson('/api/v1/clubs/' . $club->id, $this->payload())->assertForbidden();
        $this->getJson('/api/v1/clubs/' . $club->id)->assertForbidden();
    }

    public function test_club_update_validates_contacts_schedule_and_cover(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $this->loginStaff($club, Role::ROLE_ADMIN);
        $this->patchJson('/api/v1/clubs/' . $club->id, [
            'map_url' => 'javascript:alert(1)', 'phone' => 'abc', 'whatsapp' => '+77758761111',
            'instagram' => '@gladiators', 'schedule' => [['days' => '', 'hours' => []]],
            'cover' => 'https://example.com/photo.jpg',
        ])->assertUnprocessable()->assertJsonValidationErrors(['map_url', 'phone', 'whatsapp', 'instagram', 'schedule.0.days', 'schedule.0.hours', 'cover']);
        $this->assertDatabaseCount('club_profiles', 0);
    }

    public function test_cover_upload_replace_remove_and_partial_update(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $this->loginStaff($club, Role::ROLE_ADMIN);
        $url = '/api/v1/clubs/' . $club->id;
        $this->post($url, ['_method' => 'PATCH', 'cover' => UploadedFile::fake()->image('cover.jpg')] + $this->payload(), ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.name', 'Гладиаторы');
        $profile = $club->profile()->firstOrFail();
        $media = $profile->getFirstMedia(\App\Models\ClubProfile::MEDIA_COVER);
        $this->assertFileExists($media->getPath());
        $oldPath = $media->getPath();
        $this->patchJson($url, ['address' => 'Новый адрес'])->assertOk()
            ->assertJsonPath('data.phone', $this->payload()['phone'])->assertJsonPath('data.cover_url', $media->getUrl());
        $this->post($url, ['_method' => 'PATCH', 'cover' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseCount('club_profiles', 1);
        $this->assertDatabaseCount('media', 1);
        $this->assertFileDoesNotExist($oldPath);
        $this->patchJson($url, ['cover' => null])->assertOk()->assertJsonPath('data.cover_url', null);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_unfilled_profile_has_stable_response_and_mobile_cannot_edit(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $client = Client::create(['name' => 'Иванов Иван', 'club_id' => $club->id]);
        $this->withToken($client->createToken('mobile')->plainTextToken);
        $this->getJson('/api/mobile/v1/club')->assertOk()->assertExactJson(['data' => [
            'id' => (string) $club->id, 'name' => 'Гладиаторы', 'cover_url' => null,
            'address' => null, 'map_url' => null, 'schedule' => [], 'phone' => null, 'whatsapp' => null, 'instagram' => null,
        ]]);
        $this->patchJson('/api/v1/clubs/' . $club->id, $this->payload())->assertUnauthorized();
    }

    public function test_mobile_clients_receive_only_their_club_profile(): void {
        foreach (['Первый', 'Второй'] as $name) {
            $club = Club::create(['name' => $name]);
            $club->profile()->create($this->payload() + ['club_id' => $club->id]);
            $club->profile()->update(['address' => $name . ' адрес']);
            $client = Client::create(['name' => 'Клиент', 'club_id' => $club->id]);
            $this->app['auth']->forgetGuards();
            $this->withToken($client->createToken('mobile')->plainTextToken);
            $this->getJson('/api/mobile/v1/club')->assertOk()->assertJsonPath('data.name', $name)
                ->assertJsonPath('data.address', $name . ' адрес')->assertJsonPath('data.schedule', $this->payload()['schedule']);
        }
    }

    public function test_profile_migration_does_not_change_club_records_and_can_be_rolled_back(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $club->profile()->create($this->payload());
        (require database_path('migrations/2026_11_13_020000_create_club_profiles_table.php'))->down();
        $this->assertFalse(Schema::hasTable('club_profiles'));
        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'name' => 'Гладиаторы']);
        $this->assertSame(['id', 'name', 'created_at', 'updated_at'], Schema::getColumnListing('clubs'));
        (require database_path('migrations/2026_11_13_020000_create_club_profiles_table.php'))->up();
        $this->assertDatabaseCount('club_profiles', 0);
    }

    public function test_staff_token_cannot_read_mobile_club_and_cover_input_is_restricted(): void {
        $club = Club::create(['name' => 'Гладиаторы']);
        $this->loginStaff($club, Role::ROLE_ADMIN);
        $staff = auth('api')->user();
        $this->withToken($staff->createToken('dashboard')->plainTextToken);
        $this->getJson('/api/mobile/v1/club')->assertUnauthorized();
        foreach ([UploadedFile::fake()->create('file.pdf', 10), UploadedFile::fake()->image('large.jpg')->size(5121)] as $cover) {
            $this->post('/api/v1/clubs/' . $club->id, ['_method' => 'PATCH', 'cover' => $cover], ['Accept' => 'application/json'])
                ->assertUnprocessable()->assertJsonValidationErrors('cover');
        }
        $this->assertDatabaseCount('club_profiles', 0);
    }

    private function payload(): array {
        return [
            'address' => 'Павлодар, ул. Спортивная, 1', 'map_url' => 'https://maps.example.com/club',
            'schedule' => [['days' => 'Пн–Пт', 'hours' => '08:00–22:00']],
            'phone' => '+77758761111', 'whatsapp' => '77758761111', 'instagram' => 'gladiators8',
        ];
    }

    private function loginStaff(Club $club, int $role): void {
        $this->app['auth']->forgetGuards();
        $user = User::create(['name' => 'Сотрудник', 'club_id' => $club->id]);
        Role::firstOrCreate(['id' => $role], ['name' => 'Роль']);
        $user->roles()->attach($role);
        $user->clubs()->attach($club->id);
        $this->actingAs($user, 'api');
    }

}
