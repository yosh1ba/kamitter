<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\TwitterController;
use App\Library\Friendship;
use App\TwitterUser;
use App\UnfollowedList;
use Illuminate\Http\Request;

// 自動アンフォロー用コントローラー
class UnfollowController extends Controller
{
  /*
  * 自動アンフォロー用メソッド
  * ユーザー情報を引数に取り、自動アンフォローを行う
  * @param $request  Twitterアカウント情報
  * @param $restart  一時停止から再開した場合の判定
  * @param 各コントローラのメソッドインジェクション
  * @return なし
  */
  public function autoUnfollow(String $id, $restart = null)
  {

    $user = TwitterUser::find($id);

    // リスタートではない場合、アンフォローターゲットリストを取得
    if ($restart === null) {
      // 片思い中のアカウントを取得
      $oneways = $this->queryUnfollowTargetList($id, $user['twitter_screen_name']);
    }

    // フォロー後に7日経過してもフォロー返しがないユーザを$while_ago_followに格納する
    $followed_lists = FollowController::queryFollowedLists($id, 7);
    $while_ago_follow = [];
    foreach ($followed_lists as $friend) {
      array_push($while_ago_follow, $friend->user_id);
    }

    // 片思い中のアカウントと($oneways)、7日間フォロー返しがないアカントをマッチング
    $targets = array_intersect($oneways, $while_ago_follow);

    // 15日間ツイートがないアカウントを$inactive_usersに格納
    $friendship = new Friendship;
    $inactive_users = $friendship->queryInactiveUsers($id, $user['twitter_screen_name'], 15);

    // $targetsと$inactive_usersで共通するアカウントを$merge_targetsに格納
    // 重複を削除
    $merge_targets = array_merge($targets, $inactive_users);
    $unique_targets = array_unique($merge_targets);

    // キー番号を振り直し
    $result_targets = array_values($unique_targets);

    $request_params = [];
    $request_params['url'] = 'friendships/destroy';
    $request_params['params'] = [
      'id' => ''
    ];

    $user = TwitterUser::find($id)->get();

    /*
     * $result_targetsの全てについて、アンフォローを実施
     * 1アンフォローごとに15秒間隔を開ける
     */
    foreach ($result_targets as $target) {
      // 自動処理無効もしくは一時停止の場合、処理を中止する
      if(JudgeController::judgeAutoUnfollow($id) === false || JudgeController::judgePaused($id) === true){
        return false;
      }

      $request_params['params']['id'] = $target;
      $twitter_controller = new TwitterController;
      $response = $twitter_controller->accessTwitterWithAccessToken(json_decode($user, true), $request_params, $id);

      // アカウント情報が返ってこない（エラーが発生した）場合、処理を中断する
      if (!property_exists($response, 'id')) {
        return false;
      } else {
        $this->createUnfollowedLists($id, $response);
      }
      sleep(15);

    }
    return false;
  }


  /*
   * アンフォロー対象ユーザー参照用メソッド
   * Twitterアカウント情報とTwitter表示名を引数に取り、
   * フォロー済みのTwitterユーザーの中で、フォロー返しの無いユーザーを返す
   * @param $request TwitterUsersテーブルのID
   * @param $screen_name  Twitter表示名(@以降の名前)
   * @return ユーザー情報
   */
  public function queryUnfollowTargetList(String $id, String $screen_name)
  {
    $request_params = [];
    $request_params['url'] = 'friends/ids.json';  // フォロー済みのTwitterID
    $request_params['params'] = [
      'cursor' => '-1',
      'count' => '5000',
      'stringify_ids' => true,
      'screen_name' => $screen_name
    ];
    $twitter_controller = new TwitterController;
    $friends = $twitter_controller->accessTwitterWithBearerTokenAsString($request_params, $id)['ids'];

    $request_params['url'] = 'followers/ids.json';  // フォロワーのTwitterID
    $followers = $twitter_controller->accessTwitterWithBearerTokenAsString($request_params, $id)['ids'];

    // フォロー返しの無いTwitterユーザー情報を返す
    return array_diff($friends, $followers);
  }


  /*
   * UnFollowedLists(アンフォロー済みリスト)を作成するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   * @param $request TwitterUsersテーブルのID
   * @param $obj アンフォロー済みアカウントの情報
   * @return レスポンス
   */
  public function createUnfollowedLists(String $id, Object $obj){

    $create_column = [
      'user_id' => $obj->id_str,
      'screen_name' => $obj->screen_name,
      'twitter_user_id' => $id
    ];

    return UnfollowedList::create($create_column);
  }
}
