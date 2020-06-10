<?php

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


Auth::routes(['verify' => true]);

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

    Route::get('/email/verify/{id}', 'Auth\VerificationController@verify')->name('verification.verify');

    Route::post('/email/resend', 'Auth\VerificationController@resend')->name('verification.resend');

  });

  Route::middleware('verified')->group(function() {

    // 本登録ユーザーだけ表示できるページ
    Route::get('verified',  function(){

        return '本登録が完了してます！';

    });

});

// APIのURL以外のリクエストに対してはindexテンプレートを返す
// 画面遷移はフロントエンドのVueRouterが制御する
Route::get('/{any?}', fn() => view('home'))->where('any', '.+');
