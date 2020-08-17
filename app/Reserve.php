<?php

namespace App;

use App\Traits\TwitterUserInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{

  use TwitterUserInfo;

  // 以下に指定するカラムのみデータの挿入を許す
  protected $fillable = [
    'reserved_at',
    'tweet',
    'is_posted',
    'twitter_user_id',
    'unique_key'
  ];

  // 認証済みTwitterアカウントのID(twitter_usersテーブルの主キー)を引数に取り、それに紐づくクエリ結果を返す
  public function scopeOfTwitterUserId($query, $twitter_user_id)
  {
    return $this->whereTwitterUserId($query,$twitter_user_id);
  }

  // 実行時の予約時刻に該当するデータを返す
  // 実行時の誤差を考慮し、前後30秒を指定時刻とする
  public function scopeReservedAtNow($query)
  {
    return $query->whereBetween('reserved_at', [Carbon::now()->subSeconds(30), Carbon::now()->addSeconds(30)]);
  }

  // 未投稿のツイートを返す
  public function scopeNeverPosted($query)
  {
    return $query->where('is_posted',false);
  }

  // 一意キー（予約を特定するためのオリジナル文字列）に一致するデータを返す
  public function scopeOfUniqueKey($query, $unique_key)
  {
    return $query->where('unique_key', $unique_key);
  }

}