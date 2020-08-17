<?php

namespace App;

use App\Traits\TwitterUserInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FollowedList extends Model
{
  use TwitterUserInfo;

  protected $fillable = [
    'user_id',
    'screen_name',
    'followed_at',
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

  // 1ヶ月以内にフォローしたアカウントを返す
  public function scopeFollowedWithinAMonth($query)
  {
    return $query->where('followed_at', '>', Carbon::now()->subMonth());
  }

  // 引数とした日数より前にフォローしたアカウントを返す
  public function scopeFollowedSomeDaysAgo($query, $day)
  {
    return $query->where('followed_at', '<', Carbon::now()->subDay($day));
  }


}
