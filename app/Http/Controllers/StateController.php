<?php

namespace App\Http\Controllers;

use App\Jobs\AutoFollowJob;
use App\Reserve;
use App\TwitterUser;
use Illuminate\Http\Request;

/*
 * 状態管理用コントローラー
 * ・自動処理の開始、停止状態判定
 * ・is_○○○○, ○○○○_enabled 等の状態判定
 * を行う
 */
class StateController extends Controller
{
  /*
   * 一時停止メソッド
   * ユーザー情報を引数に取り、一時停止判定用カラムを変更する
   * @param $request  Twitterアカウント情報
   * @return なし
   */
  public function toPause(Request $request)
  {
    $update_column = ['pause_enabled' => true];

    TwitterUser::find($request->route('id'))
      ->update($update_column);

    return false;
  }


  /*
   * 再開メソッド
   * ユーザー情報を引数に取り、一時停止判定用カラムを変更し、自動処理を再開する
   * @param $request  Twitterアカウント情報
   * @return なし
   */
  public function toRestart(Request $request)
  {
    $update_column = ['pause_enabled' => false];

    TwitterUser::find($request->route('id'))
      ->update($update_column);

    // 自動処理再開
    AutoFollowJob::dispatch($request, (bool)TRUE);

    return false;
  }

  /*
   * 自動処理停止用メソッド
   * ユーザー情報を引数に取り、
   * 　・自動処理判定用カラム
   * 　・一時停止判定用カラム
   * を変更し、自動処理を停止する
   * @param $request  Twitterアカウント情報
   * @return なし
   */
  public function toCancel(Request $request)
  {
    TwitterUser::find($request->route('id'))
      ->UpdateState('follow');

    return false;
  }


  /*
  * 自動アンフォロー更新用メソッド
  * ユーザー情報とフロント側の自動アンフォローのチェックの値を引数に取り、
  * 　・自動アンフォロー判定用カラム
  * を変更する
  * @param $request  Twitterアカウント情報、自動アンフォローチェック状態
  * @return なし
  */
  public function updateUnfollow(Request $request)
  {
    $data = $request->all();

    $update_column = [ 'auto_unfollow_enabled' => $data['unfollow'] ];
    TwitterUser::find($data['id'])->update($update_column);

    return false;
  }

  /*
   * Reserves(予約ツイート)更新用メソッド
   * 予約ツイートIDを引数に取り、投稿済み判定用カラム(is_posted)をtrueにする
   * @param $id 予約ツイートID
   * @return なし
   */
  public function updateReserves(String $id){
    $target = new Reserve();

    $update_column = ['is_posted' => true];
    $target->find($id)
      ->update($update_column);

    return false;
  }


  /*
   * 自動いいね判定値変更用メソッド
   * ユーザー情報を引数に取り、自動いいねの判定値を変更する
   * @param $request  Twitterアカウント情報
   * @return 無し
   */
  public function updateFavorite(Request $request)
  {
    $data = $request->all();

    // 自動いいねを有効にする場合のみ、現在時刻をDBへセットする
    if($data['favorite'] == true){
      TwitterUser::find($data['id'])->update([
        'auto_favorite_enabled' => $data['favorite'],
        'auto_favorite_enabled_at' => now()
      ]);
    }else {
      TwitterUser::find($data['id'])->update([
        'auto_favorite_enabled' => $data['favorite']
      ]);
    }
    return false;
  }
}
