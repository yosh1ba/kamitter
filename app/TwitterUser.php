<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterUser extends Model
{
    // 以下に指定するカラムのみデータの挿入を許す
    protected $fillable = [
      'provider_user_id ',
      'auto_follow_enabled',
      'auto_unfollow_enabled',
      'auto_like_enabled',
      'user_id'
    ];

    public function user()
    {
      // ツイッターユーザーからアプリユーザーを取得できるようにする
      return $this->belongsTo('App\User');
    }
}
