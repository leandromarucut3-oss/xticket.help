<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedRepliesTable extends Migration
{
    public function up()
    {
        Schema::create('saved_replies', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_replies');
    }
}
