<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnfollowedLists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unfollowed_lists', function (Blueprint $table) {
          $table->id();
          $table->string('user_id');
          $table->string('screen_name');
          $table->timestamp('unfollowed_at');
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
        Schema::dropIfExists('unfollowed_lists');
    }
}
