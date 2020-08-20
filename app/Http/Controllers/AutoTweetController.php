<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\TwitterController;
use App\Reserve;
use App\TwitterUser;

// 自動ツイート用コントローラー
class AutoTweetController extends Controller
{
  /*
   * 予約ツイート投稿用メソッド
   * 予約ツイートを投稿する
   * メソッドはcron
   * @return なし
   */
  public function autoTweet()
  {
    // 現在時刻をキーに、reserveテーブルを検索
    // is_postedがfalseの値を探す
    $reserves = Reserve::ReservedAtNow()
      ->NeverPosted()
      ->get();

    if($reserves->count() === 0){
      return false;
    }
    $request_params = [];
    $request_params['url'] = 'statuses/update';
    $request_params['params'] = [
      'status' => '',
    ];

    // 該当した予約ツイートについて投稿を行う
    foreach ($reserves as $reserve){
      $request_params['params']['status'] = $reserve['tweet'];
      $user = TwitterUser::find($reserve['twitter_user_id'])->get();

      $twitter_controller = new TwitterController;
      $twitter_controller->accessTwitterWithAccessToken(json_decode($user, true), $request_params, $reserve['twitter_user_id']);

      // 投稿済み判定用カラムを変更する
      $state_controller = new StateController;
      $state_controller->updateReserves($reserve['id']);
    }
    return false;
  }
}
