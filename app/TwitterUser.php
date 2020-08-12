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

    /*
     * ログイン済みユーザーに紐づくTwitterUsers情報参照用メソッド
     * ユーザーIDを引数に取り、それに紐づくTwitterUsersテーブルの情報を返す
     * フロント側での画面描画に使用する
     * @param $id ユーザーID
     * @return クエリ結果
     */
    public function scopeOfUserId($query, $user_id)
    {
      return $query->where('user_id', $user_id);
    }

    public function scopeOfId($query, $id)
    {
      return $query->find($id);
    }

    public function scopeUpdateState($query, $result){
      switch ($result){
        case 'error':
          $update_column = [
            'auto_follow_enabled' => false,
            'pause_enabled' => false,
            'auto_favorite_enabled' => false,
            'is_waited' => false
          ];
          break;
        case 'follow':
          $update_column = [
            'auto_follow_enabled' => false,
            'pause_enabled' => false,
            'is_waited' => false
          ];
          break;
        case 'favorite':
          $update_column = [
            'auto_favorite_enabled' => false
          ];
          break;
      }

      return $query->update($update_column);
    }

    public function scopeAutoFavoriteEnabled($query)
    {
      return $query->where('auto_favorite_enabled', true);
    }

    public function scopeAutoFavoriteEnabledFifteenMinutesAgo($query)
    {
      return $query->where('auto_favorite_enabled_at', '<', Carbon::now()->subMinutes(15));
    }

    public function user()
    {
      // ユーザーからTwitterユーザーを取得できるようにする
      return $this->belongsTo('App\User');
    }
}
