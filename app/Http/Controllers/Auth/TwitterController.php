<?php

namespace App\Http\Controllers\Auth;

use App\TargetAccountList;
use App\TwitterUser;
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

  public function createFollowerTargetList(Request $request)
  {
    $this->createTargetAccountList($request);

//    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン
//    $request_url = 'https://api.twitter.com/1.1/followers/list.json' ;  // エンドポイント
//
//    $response = Http::withToken($bearer_token)->get(
//      $request_url, [
//        'screen_name' => $request->name
//      ]
//    );
//
//    // JSONを多次元配列へデコードする
//    $decoded_response = json_decode($response,true)['users'];
//
//    // プロフィールに日本語が含まれているアカウントのみを抽出する
//    $filterd_response = array_filter($decoded_response, function ($element){
//      return preg_match( '/[ぁ-ん]+|[ァ-ヴー]+|[一-龠]/u', $element['description']);
//    });
//
//    Log::debug($filterd_response);

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

    $response = TargetAccountList::where('twitter_user_id', $request->route('id'))->get('screen_name');
    return $response;
  }

}
