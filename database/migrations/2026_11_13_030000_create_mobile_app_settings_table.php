<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('mobile_app_settings', function (Blueprint $table) {
            $table->id();
            $table->json('contacts');
            $table->json('app');
            $table->json('maintenance');
            $table->json('links');
            $table->timestamps();
        });

        DB::table('mobile_app_settings')->insert([
            'id' => 1,
            'contacts' => json_encode([
                ['title' => 'Позвонить', 'url' => 'tel:+77758761111'],
                ['title' => 'Написать в WhatsApp', 'url' => 'https://wa.me/77758761111'],
            ], JSON_UNESCAPED_UNICODE),
            'app' => json_encode(['min_version' => '1.0.0', 'store_url' => ['ios' => '#', 'android' => '#']]),
            'maintenance' => json_encode(['enabled' => false, 'message' => null]),
            'links' => json_encode(['privacy_policy' => '#']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('mobile_app_settings');
    }
};
