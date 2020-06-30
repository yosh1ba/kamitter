<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FollowerTargetList extends Model
{
  protected $fillable = [
    'screen_name',
    'is_followed',
    'twitter_user_id'
  ];

  public function twitter_user()
  {
    // ツイッターユーザーからアプリユーザーを取得できるようにする
    return $this->belongsTo('App\TwitterUser');
  }
}
