<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePushDevicesTable extends Migration
{
    public function up(): void {
        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            // Device is forgotten together with the session token it was registered with
            $table->foreignId('personal_access_token_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 16);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('push_devices');
    }
}
