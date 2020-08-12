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
    'twitter_user_id'
  ];

  /*
     * 認証済みTwitterアカウントのID(twitter_usersテーブルの主キー)を引数に取り、
     * それに紐づくクエリ結果を返す
     */
  public function scopeOfTwitterUserId($query, $twitter_user_id)
  {
    return $this->whereTwitterUserId($query,$twitter_user_id);
  }

  public function scopeReservedAtNow($query)
  {
    return $query->whereBetween('reserved_at', [Carbon::now()->subSeconds(30), Carbon::now()->addSeconds(30)]);
  }

  public function scopeNeverPosted($query)
  {
    return $query->where('is_posted',false);
  }


}