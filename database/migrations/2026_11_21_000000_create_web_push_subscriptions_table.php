<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebPushSubscriptionsTable extends Migration
{
    public function up(): void {
        Schema::create('web_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('endpoint');
            // Endpoints can exceed the index length limit, so uniqueness is kept on their hash
            $table->char('endpoint_hash', 64)->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 16);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('web_push_subscriptions');
    }
}
