<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeginKeyFromUnfollowedListsToTwitterUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unfollowed_lists', function (Blueprint $table) {
          $table->foreign('twitter_user_id')->references('id')->on('twitter_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unfollowed_lists', function (Blueprint $table) {
          $table->dropForeign(['twitter_user_id']);
        });
    }
}
