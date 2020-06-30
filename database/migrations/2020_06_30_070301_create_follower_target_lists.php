<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowerTargetLists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('follower_target_lists', function (Blueprint $table) {
            $table->id();
            $table->string('screen_name');
            $table->boolean('is_follo');
            $table->unsignedBigInteger('twitter_user_id');
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
        Schema::dropIfExists('follower_target_lists');
    }
}
