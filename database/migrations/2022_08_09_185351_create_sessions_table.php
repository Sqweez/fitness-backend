<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->index();
            $table->unsignedInteger('start_user_id')->index();
            $table->unsignedInteger('finish_user_id')->index()->nullable();
            $table->unsignedInteger('club_id')->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('trinket_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
