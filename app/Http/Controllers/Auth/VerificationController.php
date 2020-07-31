<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function Psy\debug;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
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
      // 1分間に6回までアクセスを許可する
      $this->middleware('throttle:6,1');
    }

    /*メールアドレス認証用メソッド*/
    public function verify(Request $request)
    {
      // 署名付きURLに付与されたユーザーIDからユーザー情報を検索
      $user = User::find($request->route('id'));

      // email_verified_at カラムがnullかどうかを判定
      if(!$user->email_verified_at)
      {
        // nullの場合、email_verified_atを更新しユーザ情報を再取得する
        $user->markEmailAsVerified();
        event(new Verified($user));
        return new JsonResponse('Email Verified', 200);
      }
      return new JsonResponse('Email Verify Failed', 422);
    }
}
