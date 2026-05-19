<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price');
            $table->integer('prolongation_price')->default(0);
            $table->integer('unlimited_price');
            $table->integer('validity_days')->nullable();
            $table->integer('validity_minutes')->nullable();
            $table->integer('entries_count')->nullable();
            $table->unsignedInteger('club_id');
            $table->unsignedSmallInteger('service_type_id')->default(null);
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
        Schema::dropIfExists('services');
    }
}
