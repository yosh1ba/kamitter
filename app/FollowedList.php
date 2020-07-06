<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FollowedList extends Model
{
  protected $fillable = [
    'screen_name',
    'followed_at',
    'twitter_user_id'
  ];
}
