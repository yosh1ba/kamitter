<?php

namespace App\Http\Controllers;

use App\Reserve;
use Illuminate\Http\Request;

// 自動ツイート予約用コントローラー
class ReserveController extends Controller
{
  /*
   * 予約ツイート設定用メソッド
   * ユーザー情報を引数に取り、予約ツイートを設定する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function reserveTweet(Request $request)
  {
    // フォームから予約ツイート内容と予約時間を取得
    $tweet = [];
    $tweet = $request->all();

    // DBへ格納
    $target_column = [
      'twitter_user_id' => $tweet['twitter_user_id'],
      'unique_key' => $tweet['unique_key']
    ];
    $update_column = [
      'reserved_at' => date('Y-m-d H:i:s', strtotime($tweet['reserved_at'])),
      'tweet' => $tweet['tweet'],
      'is_posted' => false,
    ];

    $response = Reserve::updateOrCreate($target_column, $update_column);

    return response()->json($response);
  }

  /*
   * 予約ツイート削除用メソッド
   * ユーザー情報を引数に取り、対象のデータを削除する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function deleteReserveTweet(Request $request)
  {
    // フォームから予約ツイート内容と予約時間を取得
    $tweet = [];
    $tweet = $request->all();

    return Reserve::OfTwitterUserId($tweet['twitter_user_id'])
      ->OfUniqueKey($tweet['unique_key'])
      ->delete();
  }


  /*
   * 予約ツイート情報取得用メソッド
   * ユーザー情報を引数に取り、それに紐づくReservesテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryReserve(Request $request)
  {
    return Reserve::OfTwitterUserId($request->route('id'))
      ->NeverPosted() // 未投稿を対象とする
      ->get();
  }
}