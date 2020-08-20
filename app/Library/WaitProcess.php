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

      ignore_user_abort(true);
      set_time_limit(500);

      ob_start();
      echo 'ok'."\n";
      header('Connection: close');
      header('Content-Length: '.ob_get_length());
      ob_end_flush();
      ob_flush();
      flush();

      sleep(960);
      Log::debug('待機終了');

      TwitterUser::find($request->route('id'))->update([
        'is_waited' => false,
      ]);

      return false;
    }
  }