<?php

namespace App\Http\Controllers\Auth;

use App\Library\MailReady;
use App\TwitterUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Abraham\TwitterOAuth\TwitterOAuth;

// Twitter認証系コントローラー
class TwitterController extends Controller
{

  // 認証ページへのリダイレクト用URLを返す
  public function redirectToProvider()
  {

    // 毎回認証画面を出すため、force_loginパラメータを付与
    $redirect = Socialite::driver('twitter')->redirect();
    $redirect->setTargetUrl($redirect->getTargetUrl() . '&force_login=true');

    // JSON形式でリダイレクトURLを返す
    return response()->json([
      'redirect_url' => $redirect->getTargetUrl(),
    ]);
  }

  /*
   * Socialiteを使用して、Twitterログイン認証を行う
   * ログインに成功した場合、
   * 　すでにTwitterUsersテーブルに情報があれば、その情報を返す
   * 　無ければ、ユーザー情報を登録する
   * という処理を行う。
  */
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

        $update_column = [
          'twitter_oauth_token' => $token,  // アクセストークン
          'twitter_oauth_token_secret' => $token_secret,  // アクセストークンシークレット
          'twitter_name' => $user->getName(), // Twitterユーザー名
          'twitter_screen_name' => $user->getNickname(),  // Twitterユーザー名(@マーク以降)
          'twitter_avatar' => str_replace('http', 'https', $user->getAvatar()), // アイコン(https化)
        ];
        $twitter_user->update($update_column);
      }
      return response()->json($user);

    } catch (Exception $e) {
      return false;
    }
  }

  /*
   * ベアラートークンを利用してTwitterAPIにアクセスするメソッドです。
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   *
   * @param $arr リクエスト用パラメータ
   * @param $request TwitterUsersテーブルのID
   * @return レスポンス
   */
  public function accessTwitterWithBearerToken(Array $arr, Request $request = null)
  {
    $url = $arr['url'];
    $params = $arr['params'];

    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン

    $response = Http::withToken($bearer_token)->get('https://api.twitter.com/1.1/'. $url, $params);

    // エラーが発生した場合、処理を停止する
    if(isset($response['errors'])){
      $data = [];
      $data['message'] =
        '自動処理が停止しました。
      アカウントをご確認下さい。
      ';
      $data['subject'] = '[神ったー]自動処理停止のお知らせ';
      $mail = new MailReady;
      $mail->sendMailReady($request, $data);

      TwitterUser::find($request->route('id'))
        ->UpdateState('error');

      exit();
    }
    return $response;
  }

  /*
  * ベアラートークンを利用してTwitterAPIにアクセスするメソッドです。
  * リクエスト用パラメータを引数に取り、レスポンスを返します。
  * Httpリクエスト($request)ではなく、Stringで引数を受け取ります。
  *
  * @param $arr リクエスト用パラメータ
  * @param $id  TwitterUsersテーブルの主キー
  * @return レスポンス
  */
  public function accessTwitterWithBearerTokenAsString(Array $arr, String $id = null)
  {
    $url = $arr['url'];
    $params = $arr['params'];

    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン

    $response = Http::withToken($bearer_token)->get('https://api.twitter.com/1.1/'. $url, $params);

    // エラーが発生した場合、処理を停止する
    if(isset($response['errors'])){
      $data = [];
      $data['message'] =
        '自動処理が停止しました。
      アカウントをご確認下さい。
      ';
      $data['subject'] = '[神ったー]自動処理停止のお知らせ';
      $mail = new MailReady;
      $mail->sendMailReadyAsString($id, $data);

      TwitterUser::find($id)
        ->UpdateState('error');

      exit();
    }
    return $response;
  }

  /*
   * アクセストークンを利用してTwitterAPIにアクセスするメソッドです。
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   *
   * @param $user TwitterUsersテーブル情報
   * @param $arr リクエスト用パラメータ
   * @param $context GET or POTの判定
   * @param $request TwitterUsersテーブルのID
   * @return レスポンス
   */
  public function accessTwitterWithAccessToken(Array $user, Array $arr,string $context = 'post',Request $request = null)
  {

    $client_id = config('app.twitter_client_id');;
    $client_secret = config('app.twitter_client_secret');
    $access_token = $user[0]['twitter_oauth_token'];
    $access_token_secret = $user[0]['twitter_oauth_token_secret'];

    $connection = new TwitterOAuth($client_id, $client_secret, $access_token, $access_token_secret);

    // GETかPOSTどちらかでアクセスする
    if($context === 'get'){
      $content = $connection->get($arr['url'],$arr['params']);
    }else{
      $content = $connection->post($arr['url'],$arr['params']);
    }

    // エラーが発生した場合、処理を停止する
    if(isset($content->errors)){
      $data = [];
      $data['message'] =
        '自動処理が停止しました。
      アカウントをご確認下さい。
      ';
      $data['subject'] = '[神ったー]自動処理停止のお知らせ';
      $mail = new \App\Library\MailReady;
      $mail->sendMailReady($request, $data);

      TwitterUser::find($request->route('id'))
        ->UpdateState('error');

      exit();
    }
    return $content;
  }

  /*
   * アクセストークンを利用してTwitterAPIにアクセスするメソッドです。
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   * Httpリクエスト($request)ではなく、Stringで引数を受け取ります。
   *
   * @param $user TwitterUsersテーブル情報
   * @param $arr リクエスト用パラメータ
   * @param $context GET or POTの判定
   * @param $id TwitterUsersテーブルのID
   * @return レスポンス
   */
  public function accessTwitterWithAccessTokenAsString(Array $user, Array $arr,string $context = 'post', string $id)
  {

    $client_id = config('app.twitter_client_id');
    $client_secret = config('app.twitter_client_secret');
    $access_token = $user[0]['twitter_oauth_token'];
    $access_token_secret = $user[0]['twitter_oauth_token_secret'];

    $connection = new TwitterOAuth($client_id, $client_secret, $access_token, $access_token_secret);

    // GETかPOSTどちらかでアクセスする
    if($context === 'get'){
      $content = $connection->get($arr['url'],$arr['params']);
    }else{
      $content = $connection->post($arr['url'],$arr['params']);
    }

    // エラーが発生した場合、処理を停止する
    if(isset($content->errors)){
      $data = [];
      $data['message'] =
        '自動処理が停止しました。
      アカウントをご確認下さい。
      ';
      $data['subject'] = '[神ったー]自動処理停止のお知らせ';
      $mail = new MailReady;
      $mail->sendMailReadyAsString($id, $data);

      TwitterUser::find($id)->UpdateState('error');

      exit();
    }
    return $content;
  }


  /*
   * ログイン済みユーザーに紐づくTwitterUsers情報参照用メソッド
   * ユーザー情報を引数に取り、それに紐づくTwitterUsersテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  ユーザー情報(サービス側)
   * @return レスポンス
   */
  public function queryLinkedUsers(Request $request)
  {
    // ログインしているユーザーに紐付くtwitter_usersテーブル情報を返す
    return TwitterUser::OfUserId($request->route('id'))->get();
  }


  /*
   * 認証済みユーザー解除用メソッド
   * ユーザー情報を引数に取り、対象の認証済みユーザーの認証を解除する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function deleteAuthenticatedUser(Request $request)
  {
    return TwitterUser::find($request->route('id'))->delete();
  }
}
