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
          $table->boolean('auto_follow_enabled')->default(false);
          $table->boolean('auto_unfollow_enabled')->default(false);
          $table->boolean('auto_like_enabled')->default(false);
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
