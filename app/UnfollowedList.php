<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnfollowedList extends Model
{
  protected $fillable = [
    'user_id',
    'screen_name',
    'unfollowed_at',
    'twitter_user_id'
  ];
}
