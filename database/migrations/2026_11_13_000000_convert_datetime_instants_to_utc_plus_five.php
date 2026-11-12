<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertDatetimeInstantsToUtcPlusFive extends Migration
{
    private const COLUMNS = [
        'service_sale' => 'activated_at',
        'stop_cards' => 'unstopped_at',
        'transactions' => 'cancelled_at',
    ];

    public function up(): void {
        DB::transaction(function () {
            foreach (self::COLUMNS as $table => $column) {
                DB::table($table)->whereNotNull($column)->update([
                    $column => DB::raw("DATE_SUB(`{$column}`, INTERVAL 1 HOUR)"),
                ]);
            }
        });
    }

    public function down(): void {
        DB::transaction(function () {
            foreach (self::COLUMNS as $table => $column) {
                DB::table($table)->whereNotNull($column)->update([
                    $column => DB::raw("DATE_ADD(`{$column}`, INTERVAL 1 HOUR)"),
                ]);
            }
        });
    }
}
