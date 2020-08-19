<?php


  namespace App\Library;


  use App\TwitterUser;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Log;
  use phpDocumentor\Reflection\Types\This;

  class WaitProcess
  {
    /*
     * 自動処理待機メソッド
     * ユーザー情報と待機時間(int)を引数に取り、
     * 　・待機判定用カラム
     * を変更する。
     * Twitter APIリクエスト制限回避に用いる。(待機時間を960秒をデフォルトとする)
     * @param $request  Twitterアカウント情報
     * @return なし
    */
    public static function wait(Request $request, int $time = 960)
    {
      TwitterUser::find($request->route('id'))->update([
        'is_waited' => true,
      ]);

      Log::debug('待機開始');
      // 指定時間だけ待機

      WaitProcess::respondOK();

      sleep(960);
      Log::debug('待機終了');

      TwitterUser::find($request->route('id'))->update([
        'is_waited' => false,
      ]);

      return false;
    }

    public static function respondOK()
    {
      // check if fastcgi_finish_request is callable
      if (is_callable('fastcgi_finish_request')) {
        /*
         * This works in Nginx but the next approach not
         */
        session_write_close();
        fastcgi_finish_request();

        return;
      }

      ignore_user_abort(true);

      ob_start();
      $serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
      header($serverProtocole.' 200 OK');
      header('Content-Encoding: none');
      header('Content-Length: '.ob_get_length());
      header('Connection: close');

      ob_end_flush();
      ob_flush();
      flush();
    }
  }