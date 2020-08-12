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
      'auto_follow_enabled',
      'auto_unfollow_enabled',
      'pause_enabled',
      'auto_favorite_enabled',
      'auto_favorite_enabled_at',
      'is_waited',
      'user_id'
    ];

    protected $casts = [
      'auto_follow_enabled' => 'integer',
      'auto_unfollow_enabled' => 'integer',
      'pause_enabled' => 'integer',
      'auto_favorite_enabled' => 'integer',
      'is_waited' => 'integer',
    ];


    public function scopeLinkedUsers($query, $id)
    {
      return $query->where('user_id', $id);
    }

    public function user()
    {
      // ユーザーからTwitterユーザーを取得できるようにする
      return $this->belongsTo('App\User');
    }
}
