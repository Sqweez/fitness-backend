<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'media-library.disk_name' => 'public', 'media-library.image_optimizers' => []]);
        DB::purge('sqlite');
        Storage::fake('public');
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('role_id');
        });
        require_once database_path('migrations/2026_11_12_000000_create_trainers_table.php');
        (new \CreateTrainersTable())->up();
        require_once database_path('migrations/2022_08_06_193816_create_media_table.php');
        (new \CreateMediaTable())->up();
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_13_000000_add_expires_at_to_personal_access_tokens_table.php'))->up();
    }

    public function test_mobile_list_requires_a_client_token(): void {
        $this->getJson('/api/mobile/v1/trainers')->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_mobile_list_is_empty_without_trainers(): void {
        $this->loginClient();
        $this->getJson('/api/mobile/v1/trainers')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_trainers_migration_can_be_rolled_back(): void {
        (new \CreateTrainersTable())->down();
        $this->assertFalse(Schema::hasTable('trainers'));
        (new \CreateTrainersTable())->up();
        $this->assertTrue(Schema::hasTable('trainers'));
    }

    public function test_mobile_list_only_contains_visible_trainers_in_stable_order(): void {
        $this->loginClient();
        $last = $this->trainer(['sort_order' => 10]);
        $first = $this->trainer(['sort_order' => 1]);
        $second = $this->trainer(['sort_order' => 1]);
        $this->trainer(['sort_order' => 0, 'is_active' => false]);
        $response = $this->getJson('/api/mobile/v1/trainers')->assertOk()->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.id', (string) $first)
            ->assertJsonPath('data.1.id', (string) $second)
            ->assertJsonPath('data.2.id', (string) $last)
            ->assertJsonPath('data.0.photo_url', null);
        $this->assertSame(['id', 'first_name', 'last_name', 'specialization', 'photo_url'], array_keys($response->json('data.0')));
    }

    /** @dataProvider managers */
    public function test_manager_can_create_a_trainer_without_a_staff_account(int $role): void {
        $this->loginStaff($role);
        $response = $this->postJson('/api/v1/trainers', $this->payload())->assertCreated();
        $response->assertJsonPath('data.first_name', 'Александр')
            ->assertJsonPath('data.photo_url', null)
            ->assertJsonPath('data.sort_order', 0)
            ->assertJsonPath('data.is_active', true);
        $this->assertDatabaseCount('trainers', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertIsString($response->json('data.id'));
    }

    public static function managers(): array {
        return [[Role::ROLE_ADMIN], [Role::ROLE_BOSS]];
    }

    public function test_admin_can_list_hidden_trainers_show_update_and_delete(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $id = $this->trainer(['is_active' => false]);
        $this->getJson('/api/v1/trainers')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.is_active', false);
        $this->getJson('/api/v1/trainers/' . $id)->assertOk()->assertJsonPath('data.id', (string) $id);
        $this->patchJson('/api/v1/trainers/' . $id, ['first_name' => 'Иван', 'sort_order' => 5, 'is_active' => true])
            ->assertOk()->assertJsonPath('data.first_name', 'Иван')->assertJsonPath('data.last_name', 'Волков')
            ->assertJsonPath('data.sort_order', 5)->assertJsonPath('data.is_active', true);
        $this->deleteJson('/api/v1/trainers/' . $id)->assertNoContent();
        $this->assertDatabaseMissing('trainers', ['id' => $id]);
    }

    public function test_other_roles_cannot_manage_trainers(): void {
        $this->loginStaff(Role::ROLE_TRAINER);
        $id = $this->trainer();
        $this->getJson('/api/v1/trainers')->assertStatus(403);
        $this->getJson('/api/v1/trainers/' . $id)->assertStatus(403);
        $this->postJson('/api/v1/trainers', $this->payload())->assertStatus(403);
        $this->patchJson('/api/v1/trainers/' . $id, ['first_name' => 'Иван'])->assertStatus(403);
        $this->deleteJson('/api/v1/trainers/' . $id)->assertStatus(403);
        $this->assertDatabaseCount('trainers', 1);
    }

    public function test_mobile_token_cannot_manage_trainers(): void {
        $this->loginClient();
        $this->postJson('/api/v1/trainers', $this->payload())->assertStatus(401);
    }

    public function test_admin_input_is_validated_by_form_requests(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $this->postJson('/api/v1/trainers', [])->assertStatus(422)->assertJsonValidationErrors(['first_name', 'last_name', 'specialization']);
        $id = $this->trainer();
        $this->patchJson('/api/v1/trainers/' . $id, ['sort_order' => -1, 'first_name' => ''])
            ->assertStatus(422)->assertJsonValidationErrors(['sort_order', 'first_name']);
        $this->post('/api/v1/trainers', $this->payload() + ['photo' => UploadedFile::fake()->create('file.pdf', 10)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->patchJson('/api/v1/trainers/' . $id, ['sort_order' => 4294967296])
            ->assertStatus(422)->assertJsonValidationErrors(['sort_order']);
    }

    public function test_photo_is_uploaded_resized_replaced_and_removed(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $response = $this->post('/api/v1/trainers', $this->payload() + ['photo' => UploadedFile::fake()->image('photo.jpg', 1200, 800)], ['Accept' => 'application/json'])
            ->assertCreated();
        $id = $response->json('data.id');
        $photo = Trainer::findOrFail($id)->getFirstMedia(Trainer::MEDIA_PHOTO);
        $response->assertJsonPath('data.photo_url', $photo->getUrl('mobile'));
        $this->assertFileExists($photo->getPath('mobile'));
        [$width, $height] = getimagesize($photo->getPath('mobile'));
        $this->assertSame([600, 600], [$width, $height]);
        $oldPath = $photo->getPath();

        $this->post('/api/v1/trainers/' . $id, ['_method' => 'PATCH', 'photo' => UploadedFile::fake()->image('new.jpg', 800, 1200)], ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseCount('media', 1);
        $this->assertFileDoesNotExist($oldPath);
        $this->patchJson('/api/v1/trainers/' . $id, ['first_name' => 'Иван'])->assertOk();
        $this->assertDatabaseCount('media', 1);
        $this->patchJson('/api/v1/trainers/' . $id, ['photo' => null])->assertOk()->assertJsonPath('data.photo_url', null);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_mobile_list_returns_the_ready_photo_url(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $response = $this->post('/api/v1/trainers', $this->payload() + ['photo' => UploadedFile::fake()->image('photo.jpg')], ['Accept' => 'application/json'])->assertCreated();
        $url = $response->json('data.photo_url');
        $this->loginClient();
        $this->getJson('/api/mobile/v1/trainers')->assertOk()->assertJsonPath('data.0.photo_url', $url);
    }

    public function test_failed_photo_conversion_rolls_back_records_and_preserves_existing_files(): void {
        $this->loginStaff(Role::ROLE_ADMIN);
        $validPhoto = UploadedFile::fake()->image('valid.jpg');
        $content = substr(file_get_contents($validPhoto->getPathname()), 0, 100);
        $brokenPhoto = UploadedFile::fake()->createWithContent('broken.jpg', $content);
        $this->assertSame('image/jpeg', $brokenPhoto->getMimeType());
        $this->post('/api/v1/trainers', $this->payload() + ['photo' => $brokenPhoto], ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertDatabaseCount('trainers', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $id = $this->post('/api/v1/trainers', $this->payload() + ['photo' => $validPhoto], ['Accept' => 'application/json'])
            ->assertCreated()->json('data.id');
        $files = Storage::disk('public')->allFiles();
        $this->post('/api/v1/trainers/' . $id, [
            '_method' => 'PATCH',
            'first_name' => 'Иван',
            'photo' => UploadedFile::fake()->createWithContent('broken.jpg', $content),
        ], ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertDatabaseCount('media', 1);
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('trainers', ['id' => $id, 'first_name' => 'Александр']);
    }

    public function test_deleting_a_trainer_removes_its_photo(): void {
        $this->loginStaff(Role::ROLE_BOSS);
        $id = $this->post('/api/v1/trainers', $this->payload() + ['photo' => UploadedFile::fake()->image('photo.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()->json('data.id');
        $path = Trainer::findOrFail($id)->getFirstMedia(Trainer::MEDIA_PHOTO)->getPath();
        $this->deleteJson('/api/v1/trainers/' . $id)->assertNoContent();
        $this->assertDatabaseCount('media', 0);
        $this->assertFileDoesNotExist($path);
    }

    private function payload(): array {
        return ['first_name' => 'Александр', 'last_name' => 'Волков', 'specialization' => 'Силовые тренировки'];
    }

    private function trainer(array $attributes = []): int {
        return DB::table('trainers')->insertGetId(array_merge($this->payload(), $attributes));
    }

    private function loginClient(): void {
        $client = Client::create(['name' => 'Иванов Алексей']);
        $this->withToken($client->createToken('mobile')->plainTextToken);
    }

    private function loginStaff(int $role): void {
        $user = User::create(['name' => 'Администратор']);
        Role::create(['id' => $role, 'name' => 'Роль']);
        $user->roles()->attach($role);
        $this->actingAs($user, 'api');
    }
}
