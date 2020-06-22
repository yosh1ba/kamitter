<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TargetAccountList extends Model
{
  // 以下に指定するカラムのみデータの挿入を許す
  protected $fillable = [
    'screen_name',
    'twitter_user_id'
  ];

  public function twitter_user()
  {
    // ツイッターユーザーからアプリユーザーを取得できるようにする
    return $this->belongsTo('App\TwitterUser');
  }
}
