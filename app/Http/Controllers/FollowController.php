<?php

namespace App\Http\Controllers;

use App\FollowedList;
use App\Http\Controllers\Auth\TwitterController;
use App\Jobs\AutoFollowJob;
use App\Library\Friendship;
use App\Library\MailReady;
use App\Library\Target;
use App\TargetAccountList;
use App\TwitterUser;
use Illuminate\Http\Request;
use App\Library\WaitProcess;
use Illuminate\Support\Facades\Log;

// 自動フォロー用コントローラー
class FollowController extends Controller
{

  /*
  * 自動フォローキューイング用メソッド
  * ユーザー情報を引数に取り、自動運用のためのキューを作る（非同期処理）
  * @param $request  Twitterアカウント情報
  * @param $restart  一時停止から再開した場合の判定
  * @return 無し
  */
  public function autoFollowQueue(Request $request,$restart = null)
  {
    AutoFollowJob::dispatch($request, $restart);
  }

  /*
  * 自動フォロー用メソッド
  * ユーザー情報を引数に取り、自動運用を行う
  * 　・自動フォロー
  * 　・自動アンフォロー（フォロー数が5000を超え、フロント側の自動アンフォローにチェックがある場合)
  * これらの処理を一連で行う
  * @param $id  TwitterUsersテーブルの主キー
  * @param $restart  一時停止から再開した場合の判定
  * @return 無し
  */
  public function autoFollow(String $id,$restart)
  {
    /*
    * 自動運用判定用カラム(auto_follow_enabled)をtrueにする
    * 待機状態判定用カラム(is_waited)をfalseにする
    */
    $update_column = [
      'auto_follow_enabled' => true,
      'is_waited' => false
    ];

    TwitterUser::find($id)->update($update_column);

    // 自動アンフォローの判定値を取得
    $enable_unfollow = TwitterUser::find($id)
      ->get()[0]->auto_unfollow_enabled;

    // 認証済みアカウント情報取得
    $user = TwitterUser::find($id)->get();

    // ターゲットアカウントリスト取得
    $lists = TargetAccountList::OfTwitterUserId($id)->select('screen_name')->get();

    $count = 0; // APIリクエスト可能回数

    // リスタート時は16分間待機する
    if($restart === true ){
      WaitProcess::wait($id);
    }

    // 1ターゲットアカウント毎に自動処理を行う
    foreach ($lists as $list){
      $target_class = new Target;

      // リスタートで無い場合は、フォロワーターゲットリストを作成する
      if ($restart !== true){
        $target_class->createFollowerTargetList($id, $list->screen_name);
      }

      // 未フォローのフォロワーターゲットリストを取得
      $targets = $target_class->queryFollowerTargetList($id);

      $request_params = [];
      $request_params['url'] = 'friendships/create';
      $request_params['params'] = [
        'screen_name' => ''
      ];

      /*
       * フォロワーターゲットリストのアカウントを順番にフォローしていく。
       * 以下の通り、ウェイトを設ける。
       *  ・１アカウントフォローするごとに15秒
       *  ・15アカウントフォローするごとに16分（この間に自動いいねを行う）
       * また、5000フォローを超えた場合、自動アンフォローを開始する
       *
       * twitterAPIからエラーが返ってきた場合、その時点で処理を終了する
      */
      foreach ($targets as $target){
        Log::debug($count .'回目');
        $friendship = new Friendship;

        // 自動処理無効もしくは一時停止の場合、処理を中止する
        if(JudgeController::judgeAutoPilot($id) === false || JudgeController::judgePaused($id) === true){
          Log::debug('処理終了');
          return false;
        }
        $request_params['params']['screen_name'] = $target->screen_name;

        // 現在のフォロー数を確認する
        $friends_count = $friendship->queryFriendsCount($id ,$user);

        // フォロー数が5000人を超え、自動アンフォローがONの場合は自動アンフォローの処理を開始する
        // TODO 直す
        if($friends_count >= 100 && $enable_unfollow === 1){
          $unfollow = new UnfollowController;
          if ($restart !== true){
            $unfollow->autoUnfollow($id);
          }else {
            $unfollow->autoUnfollow($id, true);
          }
        }

        // 15カウントごとにカウントごとに16分(960秒)待機する
        if( $count !== 0  && ($count % 15) === 0 ){
          WaitProcess::wait($id);
        }

        // twitterAPIへフォローリクエストを送る
        $twitter_controller = new TwitterController;
        $response = $twitter_controller->accessTwitterWithAccessTokenAsString(json_decode($user, true),$request_params, 'post', $id);

        // アカウント情報が返ってこない（エラーが発生した）場合、処理を中断する
        if(!property_exists($response, 'id')){
          return false;
        }else {
          // フォロー成功した場合、フォロー済みリストにもアカウント情報を格納する
          $target_class->updateFollowerTargetList($id, $target->screen_name);

          $this->createFollowedLists($id, $response);
        }
        sleep(15);
        $count++;
      }
    }
    // 自動処理が完了した後に完了メールを送信する
    $data = [];
    $data['message'] =
      '自動フォロー、アンフォロー処理が完了しました。
      ご確認をお願い致します。
      ';
    $data['subject'] = '[神ったー]自動処理完了のお知らせ';
    $mail = new MailReady;
    $mail->sendMailReadyAsString($id, $data);

    TwitterUser::find($id)
      ->UpdateState('follow');

    return false;
  }


  /*
   * FollowedLists(フォロー済みリスト)を作成するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   * @param $id  TwitterUsersテーブルの主キー
   * @param $obj フォロー済みアカウントの情報
   * @return レスポンス
   */
  public function createFollowedLists(String $id, Object $obj){

    $create_column = [
      'user_id' => $obj->id_str,
      'screen_name' => $obj->screen_name,
      'twitter_user_id' => $id
    ];

    return FollowedList::create($create_column);
  }


  /*
   * フォローから一定日数以上経過したユーザー情報を返す
   * Twitterユーザー情報と日数を引数に取り、フォローから対象日数以上が経過したユーザーを返す
   * @param $id  TwitterUsersテーブルの主キー
   * @param $day  判定用日数
   * @return ユーザー情報
   */
  public static function queryFollowedLists(String $id, Int $day)
  {
    return FollowedList::OfTwitterUserId($id)
      ->FollowedSomeDaysAgo($day)
      ->select('user_id')
      ->get();
  }
}
