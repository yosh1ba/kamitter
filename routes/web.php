<?php

  use App\TwitterUser;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 同一オリジンAPI
Route::prefix('api')
  ->group(function () {

    // 登録
    Route::post('/register', 'Auth\RegisterController@register')->name('register');

    // ログイン
    Route::post('/login', 'Auth\LoginController@login')->name('login');

    // ログイン
    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

    // 認証ユーザー取得
    Route::get('/user', function(){
      return Auth::user();
    } )->name('user');

    // パスワードリセットメール送信
    Route::post('/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');

    // パスワードリセットメール送信
    Route::post('/password/reset/{token}', 'Auth\ResetPasswordController@reset');

    // メールアドレス認証
    Route::get('/email/verify/{id}', 'Auth\VerificationController@verify')->name('verification.verify');

    // メールアドレス認証用メール再送信
    Route::post('/email/resend', 'Auth\VerificationController@resend')->name('verification.resend');

    // twitter認証ユーザ取得
    Route::get('/twitter/user/{id}', 'Auth\TwitterController@queryLinkedUsers');

    // ターゲットアカウント存在チェック
    Route::post('/twitter/target/check', 'TargetController@checkTargetAccountList');

    // ターゲットアカウントリスト作成
    Route::post('/twitter/target', 'TargetController@createTargetAccountList');

    // ターゲットアカウントリスト読み込み
    Route::get('/twitter/target/{id}', 'TargetController@queryTargetAccountList');

    // サーチキーワードリスト作成
    Route::post('/search/keyword', 'SearchController@createSearchKeywordList');

    // サーチキーワードリスト読み込み
    Route::get('/search/keyword/{id}', 'SearchController@querySearchKeywordList');

    // いいねキーワードリスト作成
    Route::post('/favorite/keyword', 'FavoriteController@createFavoriteKeywordList');

    // いいねキーワードリスト読み込み
    Route::get('/favorite/keyword/{id}', 'FavoriteController@queryFavoriteKeywordList');

    // 自動フォロー
    Route::post('/twitter/follow/{id}', 'FollowController@autoFollow');

    // 自動アンフォロー状態更新
    Route::post('/twitter/unfollow/update', 'StateController@updateUnfollow');

    // 自動いいね状態更新
    Route::post('/twitter/favorite/update', 'StateController@updateFavorite');

    // 自動いいね
    Route::post('/twitter/favorite/{id}', 'FavoriteController@autoFavorite');

    // ツイート予約
    Route::post('/twitter/reserve', 'ReserveController@reserveTweet');

    // ツイート予約削除
    Route::post('/twitter/reserve/delete', 'ReserveController@deleteReserveTweet');

    // ツイート予約読み込み
    Route::get('/twitter/reserve/{id}', 'ReserveController@queryReserve');

    // twitter認証ユーザ削除
    Route::post('/twitter/user/delete/{id}', 'Auth\TwitterController@deleteAuthenticatedUser');

    // メール送信
    Route::post('/send/mail/{id}', 'MailController@sendMail');

    // 自動いいね状態読み込み
    Route::get('/twitter/auto/favorite/{id}', 'JudgeController@judgeAutoFavorite');

    // 自動運用状態読み込み
    Route::get('/twitter/auto/{id}', 'JudgeController@judgeAutoPilot');

    // 一時停止状態読み込み
    Route::get('/twitter/pause/{id}', 'JudgeController@judgePaused');

    // 自動処理を一時停止する
    Route::post('/twitter/pause/{id}', 'StateController@toPause');

    // 自動処理を再開する
    Route::post('/twitter/restart/{id}', 'StateController@toRestart');

    // 自動処理を中止する
    Route::post('/twitter/cancel/{id}', 'StateController@toCancel');

  });

  // Twitter認証ページを開く
  Route::get('/twitter', 'Auth\TwitterController@redirectToProvider');

  // Twitterユーザの登録
  Route::get('/twitter/register', 'Auth\TwitterController@handleProviderCallback');

  // APIのURL以外のリクエストに対してはindexテンプレートを返す
  // 画面遷移はフロントエンドのVueRouterが制御する
  Route::get('/{any?}', function () {
    return view('home');
  })->where('any', '.+');
