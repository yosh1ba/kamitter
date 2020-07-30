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
    Route::get('/user', fn() => Auth::user())->name('user');

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
    Route::post('/twitter/target/check', 'Auth\TwitterController@checkTargetAccountList');

    // ターゲットアカウントリスト作成
    Route::post('/twitter/target', 'Auth\TwitterController@createTargetAccountList');

    // ターゲットアカウントリスト読み込み
    Route::get('/twitter/target/{id}', 'Auth\TwitterController@queryTargetAccountList');

    // サーチキーワードリスト作成
    Route::post('/search/keyword', 'SearchController@createSearchKeywordList');

    // サーチキーワードリスト読み込み
    Route::get('/search/keyword/{id}', 'SearchController@querySearchKeywordList');

    // いいねキーワードリスト作成
    Route::post('/favorite/keyword', 'FavoriteController@createFavoriteKeywordList');

    // いいねキーワードリスト読み込み
    Route::get('/favorite/keyword/{id}', 'FavoriteController@queryFavoriteKeywordList');

    // 自動フォロー
    Route::post('/twitter/follow/{id}', 'Auth\TwitterController@autoFollow');

    // 自動アンフォロー
    Route::post('/twitter/unfollow/{id}', 'Auth\TwitterController@autoUnfollow');

    // 自動いいね
    Route::post('/twitter/favorite/{id}', 'Auth\TwitterController@autoFavorite');

    // ツイート予約
    Route::post('/twitter/reserve', 'Auth\TwitterController@reserveTweet');

    // ツイート
    Route::post('/twitter/tweet', 'Auth\TwitterController@AutoTweet');

    // ツイート予約読み込み
    Route::get('/twitter/reserve/{id}', 'Auth\TwitterController@queryReserve');

    // twitter認証ユーザ削除
    Route::post('/twitter/user/delete/{id}', 'Auth\TwitterController@deleteAuthenticatedUser');

    // メール送信
    Route::post('/send/mail/{id}', 'Auth\TwitterController@sendMail');

    // 自動運用状態読み込み
    Route::get('/twitter/auto/{id}', 'Auth\TwitterController@judgeAutoPilot');

    // 一時停止状態読み込み
    Route::get('/twitter/pause/{id}', 'Auth\TwitterController@judgePaused');

    // 自動処理を一時停止する
    Route::post('/twitter/pause/{id}', 'Auth\TwitterController@toPause');

    // 自動処理を再開する
    Route::post('/twitter/restart/{id}', 'Auth\TwitterController@toRestart');

    // 自動処理を中止する
    Route::post('/twitter/cancel/{id}', 'Auth\TwitterController@toCancel');

  });

  // Twitter認証ページを開く
  Route::get('/twitter', 'Auth\TwitterController@redirectToProvider');

  // Twitterユーザの登録
  Route::get('/twitter/register', 'Auth\TwitterController@handleProviderCallback');


  Route::middleware('verified')->group(function() {

    // 本登録ユーザーだけ表示できるページ
    Route::get('verified',  function(){

        return '本登録が完了してます！';

    });

});

// APIのURL以外のリクエストに対してはindexテンプレートを返す
// 画面遷移はフロントエンドのVueRouterが制御する
Route::get('/{any?}', fn() => view('home'))->where('any', '.+');
