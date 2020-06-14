<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // authenticatedメソッドをオーバーライドし、ログイン成功時のレスポンスをカスタマイズする
    // ログイン成功時にユーザー情報を返す
    protected function authenticated(Request $request, $user)
    {
        return $user;
    }

    // loggedOutメソッドをオーバーライドし、ログアウト成功時のレスポンスをカスタマイズする
    // ログアウト成功時にセッションを再作成する
    protected function loggedOut(Request $request)
    {
        // セッションを再生成する
        $request->session()->regenerate();

        return response()->json();
    }

    // Twitter連携用処理

    // 認証ページへのリダイレクト
    public function redirectToProvider()
    {
      return Socialite::driver('twitter')->redirect();
    }

    public function handleProviderCallback()
    {
      try{
        $twitter_user = Socialite::driver('twitter')->user();
        //アクセストークン取得
        $token = $twitter_user->token;
        $token_secret = $twitter_user->tokenSecret;

        if($twitter_user){
          // ユーザーの取得または生成
          $user = User::
        }
      } catch (

      )
    }


}
