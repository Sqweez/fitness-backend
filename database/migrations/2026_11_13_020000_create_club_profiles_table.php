У тебя<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('club_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->unique()->constrained('clubs');
            $table->string('address')->nullable();
            $table->text('map_url')->nullable();
            $table->json('schedule')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 15)->nullable();
            $table->string('instagram', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('club_profiles');
    }
};
