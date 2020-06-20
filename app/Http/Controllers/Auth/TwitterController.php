<?php

namespace App\Http\Controllers\Auth;

use App\TwitterUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Abraham\TwitterOAuth\TwitterOAuth;


class TwitterController extends Controller
{

//  public function __construct()
//  {
//    $this->middleware('session');
//
//  }

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
    $this->middleware('session');
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

  public function authenticatedUsers(Request $request){

    // ログインしているユーザーに紐付くtwitter_usersテーブル情報を返す
    return TwitterUser::where('user_id', $request->route('id'))->get();
  }
}
