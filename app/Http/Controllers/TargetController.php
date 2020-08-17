<?php

namespace App\Http\Controllers;

use App\TargetAccountList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/*
 * ターゲット系テーブルに関するコントローラー
 * 　・ターゲットアカウントリスト
 * 　・フォロワーターゲットリスト
 */
class TargetController extends Controller
{

  /*
   * 対象のツイッターアカウントが有効がどうかを判定するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   *
   * @param $request Twitter表示名(@以降の名前)
   * @return レスポンス
   */
  public function checkTargetAccountList(Request $request)
  {
    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン
    $request_url = 'https://api.twitter.com/1.1/users/show.json' ;  // エンドポイント

    $response = Http::withToken($bearer_token)->get(
      $request_url, [
        'screen_name' => $request->screen_name
      ]
    );
    // Log::debug($response->json());

    return json_decode($response,true);
  }

  /*
   * TargetAccountLists(ターゲットアカウントリスト)を作成するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   * @param $request Twitter表示名(@以降の名前)
   * @return レスポンス
   */
  public function createTargetAccountList(Request $request)
  {

    // フロント側でターゲットアカウントが空の場合h処理をスキップする
    if( empty($request->all())  ){
      return false;
    }

    $arr = [];
    foreach($request->all() as $data) {

      // messageプロパティを取り除く
      unset($data['message']);

      // created_atとupdated_atを現在時刻として追加
      $data['created_at'] = now();
      $data['updated_at'] = now();

      // 配列arrに現在のループ配列dataを追加
      array_push($arr, $data);

      // twitter_user_idの値を変数にセット
      $twitter_user_id = $data['twitter_user_id'];

    }

    // 同じtwitter_user_idのデータを一旦削除する
    $target = new TargetAccountList;
    $target->OfTwitterUserId($twitter_user_id)->delete();

    // 配列の内容をDBへインサート
    $target->insert($arr);

    return $target;
  }


  /*
   * Twitterユーザー情報に紐づくターゲットアカウント情報参照用メソッド
   * ユーザー情報を引数に取り、それに紐づくTargetAccountListsテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryTargetAccountList(Request $request)
  {
    return TargetAccountList::OfTwitterUserId($request->route('id'))->select('screen_name')->get();
  }
}
