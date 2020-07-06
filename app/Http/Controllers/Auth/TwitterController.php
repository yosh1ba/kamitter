<?php

namespace App\Http\Controllers\Auth;

use App\FollowedList;
use App\FollowerTargetList;
use App\TargetAccountList;
use App\TwitterUser;
use App\Http\Controllers\SearchController;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Abraham\TwitterOAuth\TwitterOAuth;
use PHPUnit\Util\Json;
use Psy\Util\Str;

class TwitterController extends Controller
{

  // 認証ページへのリダイレクト
  public function redirectToProvider()
  {
     // return Socialite::driver('twitter')->redirect();


    // 毎回認証画面を出すため、force_loginパラメータを付与
    $redirect = Socialite::driver('twitter')->redirect();
    $redirect->setTargetUrl($redirect->getTargetUrl() . '&force_login=true');

    // JSON形式でリダイレクトURLを返す
    return response()->json([
      'redirect_url' => $redirect->getTargetUrl(),
      // 'redirect_url' => Socialite::driver('twitter')->redirect()->getTargetUrl(),

    ]);
  }

  public function handleProviderCallback()
  {
    try {
      // twitterログイン認証
      $user = Socialite::driver('twitter')->user();
      // アクセストークン取得
      $token = $user->token;
      $token_secret = $user->tokenSecret;

      if ($user) {
        // ログイン中のユーザーID取得
        $user_id = Auth::id();

        /*
         twitter_userテーブルを検索する
          一致するものがあれば、データを返す
          一致するものがなければ、データを挿入する
        */
        $twitter_user = TwitterUser::firstOrCreate([
          'provider_user_id' => $user->id,  // twitterのユーザーID
          'user_id' => $user_id // ログイン中のユーザーID
        ]);

        $twitter_user->update(
          [
            'twitter_oauth_token' => $token,  // アクセストークン
            'twitter_oauth_token_secret' => $token_secret,  // アクセストークンシークレット
            'twitter_name' => $user->getName(), // Twitterユーザー名
            'twitter_screen_name' => $user->getNickname(),  // Twitterユーザー名(@マーク以降)
            'twitter_avatar' => $user->getAvatar(), // アイコン
          ]
        );
      }
      return response()->json($user);

    } catch (Exception $e) {
      return false;
    }
  }

  public function accessTwitterWithBearerToken(Array $arr)
  {
    $url = $arr['url'];
    $params = $arr['params'];

    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン

    $response = Http::withToken($bearer_token)->get('https://api.twitter.com/1.1/'. $url, $params);


//    Log::debug('APIレスポンス');
//    Log::debug($response);

    if(!isset($response['users'])){
      // API制限
      dd('API制限');
      // return false;
    }

    return $response;

  }

  public function accessTwitterWithAccessToken(Array $user, Array $arr)
  {

    Log::debug($user);
    $client_id = config('app.twitter_client_id');;
    $client_secret = config('app.twitter_client_secret');
    $access_token = $user[0]['twitter_oauth_token'];
    $access_token_secret = $user[0]['twitter_oauth_token_secret'];

    $connection = new TwitterOAuth($client_id, $client_secret, $access_token, $access_token_secret);

    $content = $connection->post($arr['url'],$arr['params']);

    return $content;

  }

  public function createFollowerTargetList(Request $request)
  {
    Log::debug('IN: createFollowerTargetList');

    // ターゲットアカウントを取得
    $twitter_user_id = $this->queryTargetAccountList($request);

    // APIリクエスト用のパラメータを定義
    // cursorおよびscreen_nameについては、後述のループ処理にて値を代入する
    $request_params = [];
    $request_params['url'] = 'followers/list.json';
    $request_params['params'] = [
      'cursor' => '',
      'count' => '200',
      'screen_name' => ''
    ];
    $target = new FollowerTargetList();

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $request->route('id'))->delete();

    /*
      各ターゲットアカウントごとに
      1. フォロワーを取得
      2. 各種条件で絞り込み
        ・プロフィールに日本語が含まれる
        ・プロフィールとサーチキーワードの条件が一致
        ・アンフォローリストのアカウントは除く
        ・30日以内にフォロー済みのアカウントは除く
      3. 結果をフォロワーターゲットリストに格納
    */

    foreach ($twitter_user_id as $data){

      $count = 1; // ループ回数カウント
      $limit = '1'; // APIリクエスト可能回数
      $request_params['params']['screen_name'] = $data->screen_name;  // twitterアカウント名
      $request_params['params']['cursor'] = '-1'; // APIレスポンスのカーソル

      do {
        /*
         * 15回目を実行する前に待機時間を作る
         * API制限状は15分(900秒)だが、念の為16分(960秒)を設定する
        */
        if( $limit === '0' ){
          sleep(960);
        }

        /*アプリケーション認証にてフォロワーリストを取得
        $limit にAPIリクエスト可能回数を格納
        $followers にフォロワーリストを格納*/
        $response = $this->accessTwitterWithBearerToken($request_params);
        $limit = $response->header('x-rate-limit-remaining');
        $followers = $response['users'];

        foreach($followers as $follower){

          // プロフィールに日本語が含まれるか判定
          $includedJapanese = $this->judgeIncludedJapanese($follower);

          // キーワードマッチングを行う
          $matchedKeywords = $this->judgeMatchedKeywords($request, $follower);

          // TODO アンフォローリストのアカウントを除く
          // TODO 30日以内にフォロー済みのアカウントは除く

          $alreadyFollowed = $this->alreadyFollowed($request, $follower);


          if($includedJapanese && $matchedKeywords && $alreadyFollowed === false){

            $followerQueue[] = [
              'screen_name' => $follower['screen_name'],
              'is_followed' => false,
              'twitter_user_id' => $request->route('id'),
              'created_at' => now(),
              'updated_at' => now()
            ];
          }
        }
        if (isset($followerQueue)){
          $target->insert($followerQueue);
          unset($followerQueue);
          unset($alreadyFollowed);
        }
        if (isset($followers)){
          unset($followers);
        }
        $count++;
      }while ($request_params['params']['cursor'] = $response['next_cursor_str']);
    }
    return $response;
  }


  public function judgeIncludedJapanese(Array $arr)
  {
    // プロフィールに日本語が含まれているアカウントのみを抽出する
//    $filtered_arr = array_filter($arr, function ($element){
//      return preg_match( '/[ぁ-ん]+|[ァ-ヴー]+|[一-龠]/u', $element['description']);
//    });

    if(preg_match( '/[ぁ-ん]+|[ァ-ヴー]+|[一-龠]/u', $arr['description'])){
      return true;
    }
    return false;
  }

  public function judgeMatchedKeywords(Request $request, Array $follower){
    // サーチキーワードリストからwhere句を生成
    $search = new SearchController;
    $condition = $search->makeWhereConditions($request);

    $judgeAND = null;
    $judgeOR = null;
    $judgeNOT = null;

    // AND, OR, NOTそれぞれについて、判定を行う
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
     * AND、OR条件どちらにも一致しない、または、NOT条件に一致する場合、
     * マッチング結果はfalseとする
     * */
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

  public function alreadyFollowed(Request $request, Array $follower)
  {

    $target = FollowedList::where('twitter_user_id', $request->route('id'))
      ->where('screen_name', $follower['screen_name'])
      ->where('followed_at', '>', Carbon::now()->subMonth())
      ->get();
     if ($target->isEmpty()){
       return false;
     }else{
       return true;
     }
  }

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

  public function createTargetAccountList(Request $request)
  {

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

    $target = new TargetAccountList;

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $twitter_user_id)->delete();

    // 配列の内容をDBへインサート
    $target->insert($arr);


    return $target;
  }

  public function createFollowedLists(Request $request, String $screen_name){
    $follower = FollowedList::create([
      'screen_name' => $screen_name,
      'twitter_user_id' => $request->route('id')
    ]);

    return $follower;
  }

  public function queryLinkedUsers(Request $request)
  {

    // ログインしているユーザーに紐付くtwitter_usersテーブル情報を返す
    return TwitterUser::where('user_id', $request->route('id'))->get();
  }

  public function queryTargetAccountList(Request $request)
  {

    $response = TargetAccountList::where('twitter_user_id', $request->route('id'))->select('screen_name')->get();
    return $response;
  }

  public function queryFollowerTargetList(Request $request)
  {

    $response = FollowerTargetList::where('twitter_user_id', $request->route('id'))->select('screen_name')->get();
    return $response;
  }

  public function queryAuthenticatedUser(Request $request)
  {

    $response = TwitterUser::where('id', $request->route('id'))->select(
      'twitter_screen_name',
      'twitter_oauth_token',
      'twitter_oauth_token_secret'
    )->get();
    return $response;
  }


  public function autoFollow(Request $request)
  {
    Log::debug('IN: autoFollow');

    // フォロワーターゲットリスト作成
    $response= $this->createFollowerTargetList($request);
    $response = null;

    // 自動フォロー開始
    // 認証済みアカウント情報取得
    $user = $this->queryAuthenticatedUser($request);

    // フォロワーターゲットリスト取得
    $targets = $this->queryFollowerTargetList($request);

    $request_params = [];
    $request_params['url'] = 'friendships/create';
    $request_params['params'] = [
      'screen_name' => ''
    ];
    $count = 0; // APIリクエスト可能回数

    /*
     * フォロワーターゲットリストのアカウントを順番にフォローしていく。
     * 以下の通り、ウェイトを設ける。
     *  ・１アカウントフォローするごとに30秒
     *  ・15アカウントフォローするごとに16分
     * twitterAPIからエラーが帰ってきた場合、その時点で処理を終了する
    */
    foreach ($targets as $target){
      $request_params['params']['screen_name'] = $target->screen_name;
      if( $count !== 0  && ($count % 15) === 0 ){
        sleep(960);
      }
      // twitterAPIへフォローリクエストを送る
      $response = $this->accessTwitterWithAccessToken(json_decode($user, true), $request_params);

      // アカウント情報が返ってこない　＝　エラーが発生した場合、処理を中断する
      if(!property_exists($response, 'id')){
        return false;
      }else {
        // フォロー成功した場合、フォロー済みリストにもアカウント情報を格納する
        $response = $this->createFollowedLists($request, $target->screen_name);
      }
      sleep(30);
      $count++;
    }
    return $response;

  }

}
