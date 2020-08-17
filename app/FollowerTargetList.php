<?php

namespace App;

use App\Traits\TwitterUserInfo;
use Illuminate\Database\Eloquent\Model;

class FollowerTargetList extends Model
{

  use TwitterUserInfo;

  protected $fillable = [
    'screen_name',
    'is_followed',
    'twitter_user_id'
  ];

  // 認証済みTwitterアカウントのID(twitter_usersテーブルの主キー)を引数に取り、それに紐づくクエリ結果を返す
  public function scopeOfTwitterUserId($query, $twitter_user_id)
  {
    return $this->whereTwitterUserId($query,$twitter_user_id);
  }

  // 認証済みTwitterアカウント名を引数に取り、それに紐づくクエリ結果を返す
  public function scopeOfScreenName($query, $screen_name)
  {
    return $this->whereTwitterScreenName($query, $screen_name);
  }

  // 未フォローのアカウントを返す
  public function scopeNotFollowed($query)
  {
    return $query->where('is_followed', false);
  }

  // ツイッターユーザーからアプリユーザーを取得できるようにする
  public function twitter_user()
  {
    return $this->belongsTo('App\TwitterUser');
  }
}
