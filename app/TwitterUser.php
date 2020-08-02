<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterUser extends Model
{
    // 以下に指定するカラムのみデータの挿入を許す
    protected $fillable = [
      'provider_user_id',
      'twitter_oauth_token',
      'twitter_oauth_token_secret',
      'twitter_name',
      'twitter_screen_name',
      'twitter_avatar',
      'auto_pilot_enabled',
      'pause_enabled',
      'user_id'
    ];

    protected $casts = [
      'auto_pilot_enabled' => 'integer',
      'pause_enabled' => 'integer',
    ];

    public function user()
    {
      // ユーザーからTwitterユーザーを取得できるようにする
      return $this->belongsTo('App\User');
    }
}
