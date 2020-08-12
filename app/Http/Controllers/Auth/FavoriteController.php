<?php

namespace App\Http\Controllers\Auth;

use App\FavoriteKeywordList;
use App\TwitterUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

/*
 * 自動いいね用処理クラス
 */
class FavoriteController extends Controller
{
  /*
   * いいね用キーワード(favorite_keyword_lists)作成用メソッド
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   *
   * @param $request フロント側のいいね用キーワード情報
   * @return レスポンス
   */
  public function createFavoriteKeywordList(Request $request)
  {

    $arr = [];
    foreach ($request->all() as $data) {

      if (array_key_exists('is_empty', $data)) {
        // twitter_user_idの値を変数にセット
        $twitter_user_id = $data['twitter_user_id'];
        unset($data);
        break;
      } else {
        // messageプロパティを取り除く
        unset($data['message']);
        // optionsプロパティを取り除く
        unset($data['options']);

        // created_atとupdated_atを現在時刻として追加
        $data['created_at'] = now();
        $data['updated_at'] = now();

        // 配列arrに現在のループ配列dataを追加
        array_push($arr, $data);

        // twitter_user_idの値を変数にセット
        $twitter_user_id = $data['twitter_user_id'];
      }
    }
    $target = new FavoriteKeywordList;

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $twitter_user_id)->delete();

    if ($arr) {
      // 配列の内容をDBへインサート
      $target->insert($arr);
    }
    return $target;
  }

  /*
   * いいね用キーワード(favorite_keyword_lists)参照用メソッド
   * リクエスト用パラメータを引数に取り、設定済みのいいね用キーワードを返します。
   * フロント側で利用
   * @param $request Twitterユーザー情報
   * @return レスポンス
   */
  public function queryFavoriteKeywordList(int $id)
  {
    $response = FavoriteKeywordList::where('twitter_user_id', $id)->select('selected', 'text')->get();

    return $response;
  }

  /*
   * いいね用Where句作成用メソッド
   * リクエスト用パラメータを引数に取り、いいね用Where句を返す
   * @param $request Twitterユーザー情報
   * @return レスポンス
   */
  public function makeWhereConditions(int $id)
  {
    // サーチキーワードを配列形式で格納
    $arr = $this->queryFavoriteKeywordList($id)->toArray();

    // サーチキーワードの検索結果が空の場合はfalseを返す
    if (!$arr) {
      return false;
    }

    $converted_arr = [];  // 変換後の配列

    // AND, OR, NOTごとに配列$converted_arrにWHERE句を格納する
    foreach ($arr as $data) {

      switch ($data['selected']) {
        case 'AND':
          $converted_arr['AND'][] = [$data['text']];
          break;
        case 'OR':
          $converted_arr['OR'][] = [$data['text']];
          break;
        case 'NOT':
          $converted_arr['NOT'][] = [$data['text']];
          break;
      }
    }
    return $converted_arr;
  }

  /*
   * 自動いいね用メソッド
   * cronにて15分間隔で呼び出し、自動いいねを行う
   * @return 無し
   */
  // TODO cronで自動実行できるように変更する
  public function autoFavorite()
  {
    // 自動いいねが有効なTwitterカウントを取得
    // API制限を回避するため、自動いいねが有効になってから15分以上経過したアカウントのみ対象とする
    $accounts = TwitterUser::where('auto_favorite_enabled', true)
      ->where('auto_favorite_enabled_at', '<', Carbon::now()->subMinutes(15))
      ->get();

    // 対象がいない場合は終了
    if ($accounts->count() === 0) {
      return false;
    }

    foreach ($accounts as $account) {

      // 自動いいね有効化から1日経過しているものは、自動いいねを停止する
      if ($account->auto_favorite_enabled_at < Carbon::now()->subDays()) {
        $data = [];
        $data['message'] =
          '自動いいね処理が完了しました。
           ご確認をお願い致します。
          ';
        $data['subject'] = '[神ったー]自動いいね処理完了のお知らせ';

        $twitter_controller = app()->make('App\Http\Controllers\Auth\TwitterController');
        $twitter_controller->sendMailAsString($account->id, $data);

        TwitterUser::find($account->id)->update([
          'auto_favorite_enabled' => false
        ]);

        return false;
      }

      // いいね用キーワード取得
      $condition = $this->makeWhereConditions($account->id);

      // いいね用キーワードがない場合、自動いいねを停止する
      if ($condition === false) {
        TwitterUser::find($account->id)->update([
          'auto_favorite_enabled' => false
        ]);
        return false;
      }

      $query = '';

      // AND, OR, NOTそれぞれを用いて検索用クエリを作成する
      if (isset($condition['AND'])) {
        $query = '(';
        foreach ($condition['AND'] as $data) {
          $query = $query . ' ' . $data[0];
        }
        $query = $query . ')';
      }

      if (isset($condition['OR'])) {
        foreach ($condition['OR'] as $data) {
          $query = $query . ' OR ' . $data[0];
        }
      }

      if (isset($condition['NOT'])) {
        foreach ($condition['NOT'] as $data) {
          $query = $query . ' -' . $data[0];
        }
      }

      $query = $query . ' exclude:retweets -filter:replies';  // リツイート、リプライを除く

      $request_params = [];
      $request_params['url'] = 'search/tweets'; // ツイート検索
      $request_params['params'] = [
        'q' => $query,  // クエリ分
        'count' => 10,  // 一度にいいねを行うツイートは最大10個とする
        'result_type' => 'recent' // 最新のツイートを対象とする
      ];

      // $accountを配列化(accessTwitterWithAccessTokenAsStringで利用するため)
      $user[0] = $account;

      // ツイートを検索する
      $twitter_controller = app()->make('App\Http\Controllers\Auth\TwitterController');
      $response = $twitter_controller->accessTwitterWithAccessTokenAsString($user, $request_params, 'get', $account->id);

      $request_params = []; // リクエストパラメータ初期化
      $request_params['url'] = 'favorites/create';  // いいねを付ける
      $request_params['params'] = [
        'id' => '',
      ];

      // 自動いいね処理
      foreach ($response->statuses as $tweet) {
        /*
         * 自動フォロー、アンフォロー中
         * かつ、
         *  待機時間でない場合(is_waited === 0)
         * または
         *  フロント側操作で一時停止中でない場合(pause_enabled === 0)
         * は処理を中止する
        */
        $target = TwitterUser::find($account->id);
        if (
          ($target->auto_follow_enabled === 1 && $target->is_waited === 0) ||
          ($target->auto_follow_enabled === 1 && $target->pause_enabled === 0)
        ) {
          return false;
        }

        /*
         * 自動いいねのループ処理中にフロント側で処理停止ボタンがクリックされた場合のために
         * 現時点の自動いいねの判定値を取得し、処理を継続するかの分岐を行う
        */
        $check_state = TwitterUser::find($account->id)->auto_favorite_enabled;
        /*
         * 自動いいね判定値がfalseの場合、処理を中止する
         * 判定値は処理停止ボタン側の処理で変更されるため、return false; のみを行う
        */
        if($check_state === 0){
          return false;
        }

        $request_params['params']['id'] = $tweet->id;
        $response = $twitter_controller->accessTwitterWithAccessTokenAsString($user, $request_params, 'post', $account->id);
        sleep(10);  // 10秒待機する
      }
    }
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
