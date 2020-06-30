<?php

namespace App\Http\Controllers\Auth;

use App\FollowerTargetList;
use App\TargetAccountList;
use App\TwitterUser;
use App\Http\Controllers\SearchController;
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

  public function authenticatedUsers(Request $request)
  {

    // ログインしているユーザーに紐付くtwitter_usersテーブル情報を返す
    return TwitterUser::where('user_id', $request->route('id'))->get();
  }

  public function connectTwitter(Array $arr)
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

  public function createFollowerTargetList(Request $request)
  {


    Log::debug('IN: createFollowerTargetList');

//    // サーチキーワードリストからwhere句を生成
//    $search = new SearchController;
//    $condition = $search->makeWhereConditions($request);
//
//    Log::debug('サーチキーワードの結果');
//    Log::debug($condition);
//
//    if(!$condition){
//      return response()->json([
//        'caution' => 'サーチキーワードが存在しません',
//      ]);
//    }

    // ターゲットアカウントを取得
    $twitter_user_id = $this->queryTargetAccountList($request);

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

      $followers = $this->getFollower($data->screen_name);
      $followerQueue = [];

      foreach ($followers as $follower){

        // プロフィールに日本語が含まれるか判定
        $includedJapanese = $this->judgeIncludedJapanese($follower);
        // Log::debug($includedJapanese);

        // キーワードマッチングを行う
        $matchedKeywords = $this->judgeMatchedKeywords($request, $follower);

        // TODO アンフォローリストのアカウントを除く
        // TODO 30日以内にフォロー済みのアカウントは除く

        if($includedJapanese && $matchedKeywords){

          $followerQueue[] = [
            'screen_name' => $follower['screen_name'],
            'is_followed' => false,
            'twitter_user_id' => $request->route('id'),
            'created_at' => now(),
            'updated_at' => now()
          ];
        }
      }

      $target = new FollowerTargetList();

      // 同じtwitter_user_idのデータを一旦削除する
      $target->where('twitter_user_id', $request->route('id'))->delete();

      // 配列の内容をDBへインサート
      $target->insert($followerQueue);

      // TODO 自動フォロー開始

    }

    return $target;
  }

  public function getFollower(String $screen_name)
  {
    $keys = ['id', 'name', 'screen_name', 'description'];

    $request_params = [];
    $request_params['url'] = 'followers/list.json';
    $request_params['params'] = [
      'cursor' => '-1',
      'screen_name' => $screen_name,
      'count' => '200'
    ];

    $followers = [];

     // TwitterAPIへリクエストを投げる
     // フォロワーリストを取得
    do {
      $response = $this->connectTwitter($request_params);
      $followers = array_merge($followers, $response['users']);
    }while ($request_params['params']['cursor'] = $response['next_cursor_str']);

//    Log::debug('フォロワー一覧');
//    Log::debug($followers);

    return $followers;

//    $response = Http::withToken($bearer_token)->get(
//      $request_url, [
//        'screen_name' => $screen_name
//      ]
//    );
//
//    // JSONを多次元配列へデコードする
//    $decoded_response = json_decode($response,true)['users'];
//
//    $filtered_response = [];
//    $arr = [];
//    foreach ($decoded_response as $data){
//      foreach ($keys as $key){
//        $arr[$key] = $data[$key];
//      }
//      $filtered_response[] = $arr;
//      Log::debug($filtered_response);
//    }
//     Log::debug($filtered_response);
//
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

  public function autoFollow(Request $request)
  {
    Log::debug('IN: autoFollow');

    // フォロワーターゲットリスト作成
    $response= $this->createFollowerTargetList($request);
    $response = null;

    // 自動フォロー開始
    // フォロワーターゲットリスト取得
    $target = $this->queryFollowerTargetList($request);
    Log::debug($target);



  }

}
