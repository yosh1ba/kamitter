<?php

namespace App;

use Carbon\Carbon;
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

    // ログインユーザーに紐づく認証済みTwitterアカウントを返す
    public function scopeOfUserId($query, $user_id)
    {
      return $query->where('user_id', $user_id);
    }

    // 認証済みTwitterユーザーID（主キー）に一致するアカウントを返す
    public function scopeOfId($query, $id)
    {
      return $query->find($id);
    }

    // 処理結果パターンを引数（文字列）として取り、、各種処理状態を変更する
    public function scopeUpdateState($query, $result){
      switch ($result){
        // エラー処理のの場合
        case 'error':
          $update_column = [
            'auto_follow_enabled' => false,
            'pause_enabled' => false,
            'auto_favorite_enabled' => false,
            'is_waited' => false
          ];
          break;
        // 自動フォロー（アンフォロー）処理の場合
        case 'follow':
          $update_column = [
            'auto_follow_enabled' => false,
            'pause_enabled' => false,
            'is_waited' => false
          ];
          break;
        // 自動いいね処理の場合
        case 'favorite':
          $update_column = [
            'auto_favorite_enabled' => false
          ];
          break;
      }
      return $query->update($update_column);
    }

    // 自動いいね状態をONにする
    public function scopeAutoFavoriteEnabled($query)
    {
      return $query->where('auto_favorite_enabled', true);
    }

    // 15分以上前に自動いいねを有効にしたアカウントを返す
    public function scopeAutoFavoriteEnabledFifteenMinutesAgo($query)
    {
      return $query->where('auto_favorite_enabled_at', '<', Carbon::now()->subMinutes(15));
    }

    // ユーザーからTwitterユーザーを取得できるようにする
    public function user()
    {
      return $this->belongsTo('App\User');
    }
}
