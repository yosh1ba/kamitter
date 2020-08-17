<?php

namespace App\Http\Controllers;

use App\FollowedList;
use App\TwitterUser;
use App\UnfollowedList;
use Illuminate\Http\Request;

// 条件判定用コントローラー
class JudgeController extends Controller
{
  /*
   * 自動フォロー判定用メソッド
   * TwitterUsersテーブルのIDを引数に取り、自動フォローが有効かどうか(auto_follow_enabled)を返す
   * @param $request  Twitterアカウント情報
   * @return true or false
   */
  public static function judgeAutoPilot(Request $request)
  {
    $target = TwitterUser::find($request->route('id'));

    if($target->auto_follow_enabled === 1){
      return true;
    } else {
      return false;
    }
  }

  /*
   * 自動アンフォロー判定用メソッド
   * TwitterUsersテーブルのIDを引数に取り、自動アンフォローが有効かどうか(auto_unfollow_enabled)を返す
   * @param $request  Twitterアカウント情報
   * @return true or false
   */
  public static function judgeAutoUnfollow(Request $request)
  {
    $target = TwitterUser::find($request->route('id'));

    if($target->auto_unfollow_enabled === 1){
      return true;
    } else {
      return false;
    }
  }


  /*
   * 一時停止判定用メソッド
   * TwitterUsersテーブルのIDを引数に取り、一時停止が有効かどうか(pause_enabled)を返す
   * @param $request  Twitterアカウント情報
   * @return true or false
   */
  public static function judgePaused(Request $request)
  {
    $target = TwitterUser::find($request->route('id'));

    if($target->pause_enabled === 1){
      return true;
    } else {
      return false;
    }
  }


  /*
   * 自動いいね判定用メソッド
   * TwitterUsersテーブルのIDを引数に取り、自動いいねが有効かどうか(auto_favorite_enabled)を返す
   * @param $request  Twitterアカウント情報
   * @return true or false
   */
  public static function judgeAutoFavorite(Request $request)
  {
    $target = TwitterUser::find($request->route('id'));

    if($target->auto_favorite_enabled === 1){
      return true;
    } else {
      return false;
    }
  }


  /*
  * 自分自身のアカウントでないことを判定するメソッド
  * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
  *
  * @param $user TwitterUsersテーブル情報
  * @param $request TwitterUsersテーブルのID
  * @return true or false
  */
  public static function judgeMatchedMySelf(Request $request, Array $follower)
  {
    /*
     * twitter_usersテーブルから、自分自身の情報を取得
     * フォロワーリストのアカウントに自分自身がいるかどうかを判定
     *  いる場合    >>   true
     *  いない場合   >>  false（空）
     * を返す
    */
    $target = TwitterUser::find($request->route('id'));

    if($target->twitter_screen_name === $follower['screen_name']){
      return true;
    } else {
      return false;
    }
  }

  /*
   * プロフィールに日本語が含まれるかを判定するメソッド
   * フォロワー情報を引数に取り、真偽値を返す
   *
   * @param $arr Twitterカウント情報
   * @return true or false
   */
  public static function judgeIncludedJapanese(Array $arr)
  {

    if(preg_match( '/[ぁ-ん]+|[ァ-ヴー]+|[一-龠]/u', $arr['description'])){
      return true;
    }
    return false;
  }

  /*
   * キーワード文がプロフィールに含まれるか判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $request TwitterUsersテーブルのID
   * @param $follower フォロワーリスト
   * @return true or false
   */
  public function judgeMatchedKeywords(Request $request, Array $follower){

    // サーチキーワードリストからwhere句を生成
    $search_controller = new SearchController;
    $condition = $search_controller->makeWhereConditions($request);

    $judgeAND = null;
    $judgeOR = null;
    $judgeNOT = null;

    // AND, OR, NOTそれぞれについて、判定を行う

    /*
     * ANDについて、
     * 一つでも一致しないものがある場合、その時点でfalseを返す
     */
    if(isset($condition['AND'])){
      foreach ($condition['AND'] as $data){
        if(strpos($follower['description'], $data[0]) === false){
          $judgeAND = false;
          break;
        } else{
          $judgeAND = true;
        }
      }
    }

    /*
     * ORについて、
     * 一つでも一致するものがある場合、trueを返す
     */
    if(isset($condition['OR'])){
      foreach ($condition['OR'] as $data){
        if(strpos($follower['description'], $data[0]) === false){
          $judgeOR = false;
        } else{
          $judgeOR = true;
          break;
        }
      }
    }

    /*
     * NOTについて、
     * 一つでも一致するものがある場合、falseを返す
     */
    if(isset($condition['NOT'])){
      foreach ($condition['NOT'] as $data){
        if(strpos($follower['description'], $data[0]) === false){
          $judgeNOT = false;
          break;
        } else{
          $judgeNOT = true;
        }
      }
    }

    /*
     * AND、OR条件どちらにも一致しない、または
     * NOT条件に一致する場合、マッチング結果はfalseとする
     */
    if( ($judgeAND === false && $judgeOR === false) || $judgeNOT === true ){
      return false;
    } elseif(($judgeAND === false && $judgeOR === null) || $judgeNOT === true){
      return false;
    } elseif(($judgeAND === null && $judgeOR === false) || $judgeNOT === true) {
      return false;
    } else {
      return true;
    }
  }


  /*
   * 30日以内にフォロー済みかを判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $request TwitterUsersテーブルのID
   * @param $follower フォロワーリスト
   * @return true or false
   */
  public static function alreadyFollowed(Request $request, Array $follower)
  {
    // FollowedLists(フォロー済みリスト)の中で、30日以内にフォロー済みのカウントの場合はfalseを返す
    $target =FollowedList::OfTwitterUserId($request->route('id'))
      ->OfScreenName($follower['screen_name'])
      ->FollowedWithinAMonth()
      ->get();

    if ($target->isEmpty()){
      return false;
    }else{
      return true;
    }
  }


  /*
   * アンフォロー済みかを判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $request TwitterUsersテーブルのID
   * @param $follower フォロワーリスト
   * @return true or false
   */
  public static function alreadyUnfollowed(Request $request, Array $follower)
  {
    // UnfollowedLists(アンフォローリスト)に一致するものがあれば、falseを返す
    $target = UnfollowedList::OfTwitterUserId($request->route('id'))
      ->OfScreenName($follower['screen_name'])
      ->get();

    if ($target->isEmpty()){
      return false;
    }else{
      return true;
    }
  }
}
