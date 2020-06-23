<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SearchKeywordList extends Model
{
  // 以下に指定するカラムのみデータの挿入を許す
  protected $fillable = [
    'selected',
    'text',
    'twitter_user_id'
  ];

//  public function twitter_user()
//  {
//    // ツイッターユーザーからアプリユーザーを取得できるようにする
//    return $this->belongsTo('App\TwitterUser');
//  }
}
