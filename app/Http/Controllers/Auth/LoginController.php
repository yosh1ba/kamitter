<?php

  namespace App\Http\Controllers\Auth;

  use App\TwitterUser;
  use Illuminate\Http\Request;
  use App\Http\Controllers\Controller;
  use App\Providers\RouteServiceProvider;
  use Illuminate\Foundation\Auth\AuthenticatesUsers;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Log;

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

  }
