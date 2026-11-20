<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    public function up(): void {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->unique();
            $table->unsignedBigInteger('last_message_id')->nullable()->index();
            $table->unsignedBigInteger('client_last_read_message_id')->nullable();
            $table->unsignedBigInteger('staff_last_read_message_id')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            // Staff author; null means the message was written by the client
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('text');
            $table->string('client_message_id', 64);
            $table->timestamps();
            $table->unique(['chat_id', 'client_message_id']);
            // Covers history paging and unread counters of both sides
            $table->index(['chat_id', 'user_id', 'id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chats');
    }
}
