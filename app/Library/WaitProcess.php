<?php


  namespace App\Library;


  use App\TwitterUser;
  use Illuminate\Support\Facades\Log;

  class WaitProcess
  {
    /*
     * 自動処理待機メソッド
     * ユーザー情報と待機時間(int)を引数に取り、
     * 　・待機判定用カラム
     * を変更する。
     * Twitter APIリクエスト制限回避に用いる。(待機時間を960秒をデフォルトとする)
     * @param $id  TwitterUsersテーブルの主キー
     * @param $time  待機時間(デフォルトは960秒)
     * @return なし
    */
    public static function wait(string $id, int $time = 960)
    {
      TwitterUser::find($id)->update([
        'is_waited' => true,
      ]);

      // 指定時間だけ待機
      sleep($time);

      TwitterUser::find($id)->update([
        'is_waited' => false,
      ]);

      return false;
    }
  }