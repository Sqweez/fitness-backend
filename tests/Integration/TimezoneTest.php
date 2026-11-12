<?php

namespace Tests\Integration;

use App\Http\Resources\Mobile\Client\VisitResource;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fitness_timezone_test',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => 'timezone-test-only',
        ]);
        DB::purge('mysql');
        $this->assertSame('fitness_timezone_test', DB::connection()->getDatabaseName());
        Schema::dropAllTables();
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        foreach (['service_sale' => 'activated_at', 'stop_cards' => 'unstopped_at', 'transactions' => 'cancelled_at'] as $name => $column) {
            Schema::create($name, function (Blueprint $table) use ($column) {
                $table->id();
                $table->dateTime($column)->nullable();
                $table->dateTime('active_until')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_legacy_visit_keeps_its_instant_and_duration_without_updating_data(): void {
        DB::statement("SET SESSION time_zone = '+06:00'");
        $id = DB::table('sessions')->insertGetId([
            'created_at' => '2026-11-12 19:20:00', 'finished_at' => '2026-11-12 20:20:00',
        ]);
        $epoch = DB::selectOne('SELECT UNIX_TIMESTAMP(created_at) AS epoch FROM sessions WHERE id = ?', [$id])->epoch;
        DB::purge('mysql');

        $session = Session::findOrFail($id);
        $this->assertSame('+05:00', DB::selectOne('SELECT @@session.time_zone AS timezone')->timezone);
        $this->assertSame('12.11.2026 18:20:00', format_datetime($session->created_at));
        $this->assertSame('2026-11-12T18:20:00+05:00', (new VisitResource($session))->resolve()['at']);
        $this->assertSame((int) $epoch, $session->created_at->getTimestamp());
        $this->assertSame('2026-11-12 19:20:00', $session->finished_at);
    }

    public function test_new_visit_is_written_with_the_correct_utc_instant(): void {
        Carbon::setTestNow(Carbon::parse('2026-11-12T18:20:00+05:00'));
        $session = Session::create([]);
        DB::statement("SET SESSION time_zone = '+00:00'");

        $this->assertSame('2026-11-12 13:20:00', DB::table('sessions')->where('id', $session->id)->value('created_at'));
    }

    public function test_migration_converts_only_datetime_instants_and_is_reversible(): void {
        foreach (['service_sale' => 'activated_at', 'stop_cards' => 'unstopped_at', 'transactions' => 'cancelled_at'] as $table => $column) {
            DB::table($table)->insert([
                ['id' => 1, $column => '2026-11-12 00:20:00', 'active_until' => '2026-11-30 00:00:00', 'created_at' => '2026-11-11 23:20:00', 'updated_at' => '2026-11-11 23:20:00'],
                ['id' => 2, $column => null, 'active_until' => null, 'created_at' => null, 'updated_at' => null],
            ]);
        }
        $this->assertFileExists(database_path('migrations/2026_11_13_000000_convert_datetime_instants_to_utc_plus_five.php'));
        require_once database_path('migrations/2026_11_13_000000_convert_datetime_instants_to_utc_plus_five.php');
        $migration = new \ConvertDatetimeInstantsToUtcPlusFive();
        $migration->up();

        foreach (['service_sale' => 'activated_at', 'stop_cards' => 'unstopped_at', 'transactions' => 'cancelled_at'] as $table => $column) {
            $row = DB::table($table)->find(1);
            $this->assertSame('2026-11-11 23:20:00', $row->$column);
            $this->assertSame('2026-11-30 00:00:00', $row->active_until);
            $this->assertSame('2026-11-11 23:20:00', $row->created_at);
            $this->assertSame('2026-11-11 23:20:00', $row->updated_at);
            $this->assertNull(DB::table($table)->where('id', 2)->value($column));
        }

        $migration->down();
        $this->assertSame('2026-11-12 00:20:00', DB::table('service_sale')->where('id', 1)->value('activated_at'));
        $this->assertSame('2026-11-12 00:20:00', DB::table('stop_cards')->where('id', 1)->value('unstopped_at'));
        $this->assertSame('2026-11-12 00:20:00', DB::table('transactions')->where('id', 1)->value('cancelled_at'));
    }
}
