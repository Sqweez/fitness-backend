<?php

namespace Tests\Feature;

use App\Http\Resources\Mobile\Client\VisitResource;
use App\Models\Session;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    public function test_application_uses_utc_plus_five_on_legacy_php(): void {
        $this->assertSame('Etc/GMT-5', config('app.timezone'));
        $this->assertSame(18000, now()->getOffset());
    }

    public function test_mysql_connection_uses_the_application_offset(): void {
        $this->assertSame('+05:00', config('database.connections.mysql.timezone'));
    }

    public function test_mobile_and_admin_display_the_same_visit_time(): void {
        $session = (new Session())->newFromBuilder(['id' => 1, 'created_at' => '2026-11-12 18:20:00']);

        $this->assertSame('12.11.2026 18:20:00', format_datetime($session->created_at));
        $this->assertSame('2026-11-12T18:20:00+05:00', (new VisitResource($session))->resolve()['at']);
    }
}
