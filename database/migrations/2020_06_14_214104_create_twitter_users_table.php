<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwitterUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('twitter_users', function (Blueprint $table) {
          $table->id();
          $table->string('provider_user_id');
          $table->string('twitter_oauth_token')->nullable();
          $table->string('twitter_oauth_token_secret')->nullable();
          $table->string('twitter_name')->nullable();
          $table->string('twitter_screen_name')->nullable();
          $table->string('twitter_avatar')->nullable();
          $table->boolean('auto_pilot_enabled')->default(false);
          $table->boolean('pause_enabled')->default(false);
//          $table->boolean('auto_follow_enabled')->default(false);
//          $table->boolean('auto_unfollow_enabled')->default(false);
//          $table->boolean('auto_like_enabled')->default(false);
          $table->unsignedBigInteger('user_id');
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
        Schema::dropIfExists('twitter_users');
    }
}
