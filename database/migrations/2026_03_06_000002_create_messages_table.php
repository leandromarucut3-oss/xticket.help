<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('conversation_id');
            $table->string('sender_role');
            $table->string('message_type');
            $table->text('text')->nullable();
            $table->string('file_url')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
