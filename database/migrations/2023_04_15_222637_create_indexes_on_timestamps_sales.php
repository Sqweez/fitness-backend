<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndexesOnTimestampsSales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            #$table->index('created_at');
            #$table->index('updated_at');
            #$table->index('user_id');
            #$table->index('client_id');
            #$table->index('club_id');
            #$table->index('salable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('created_at');
            $table->dropIndex('updated_at');
            $table->dropIndex('user_id');
            $table->dropIndex('client_id');
            $table->dropIndex('club_id');
            $table->dropIndex('salable_id');
        });
    }
}
