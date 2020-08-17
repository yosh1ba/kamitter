<?php

namespace App;

use App\Traits\TwitterUserId;
use App\Traits\TwitterUserInfo;
use Illuminate\Database\Eloquent\Model;

class TargetAccountList extends Model
{
  use TwitterUserInfo;

  // 以下に指定するカラムのみデータの挿入を許す
  protected $fillable = [
    'screen_name',
    'twitter_user_id'
  ];

  // 認証済みTwitterアカウントのID(twitter_usersテーブルの主キー)を引数に取り、それに紐づくクエリ結果を返す
  public function scopeOfTwitterUserId($query, $twitter_user_id)
  {
    return $this->whereTwitterUserId($query,$twitter_user_id);
  }

  // ツイッターユーザーからアプリユーザーを取得できるようにする
  public function twitter_user()
  {
    return $this->belongsTo('App\TwitterUser');
  }
}
