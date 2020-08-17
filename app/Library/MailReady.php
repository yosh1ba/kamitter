<?php


  namespace App\Library;


  use App\Mail\SendEmail;
  use App\TwitterUser;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Mail;

  // メール送信準備用クラス
  class MailReady
  {
    /*
    * メール送信準備用メソッド
    * ユーザー情報とメール送信用情報を引数に取り、メールを送信用メソッドを読み出す
    * @param $request  Twitterアカウント情報
    * @param $data メール送信用情報
    * @return なし
    */
    public function sendMailReady(Request $request, Array $data)
    {
      // Twitterユーザー取得
      $target = TwitterUser::find($request->route('id'));
      $user = $target->user;

      // メールに必要な情報を$dataに追記し、sedMailメソッドを呼び出す
      $data['name'] = $user['name'];
      $data['target'] = $target['twitter_screen_name'];

      Mail::to($user['email'])
        ->send(new SendEmail($data));

      return false;
    }


    /*
     * メール送信準備用メソッド
     * ユーザー情報とメール送信用情報を引数に取り、メールを送信用メソッドを読み出す
     * TwitterユーザーIDを文字列で受け取る
     * @param $id  TwitterゆーザーID
     * @param $data メール送信用情報
     * @return なし
     */
    public function sendMailReadyAsString(String $id, Array $data)
    {
      // Twitterユーザー取得
      $target = TwitterUser::find($id);
      $user = $target->user;

      // メールに必要な情報を$dataに追記し、sedMailメソッドを呼び出す
      $data['name'] = $user['name'];
      $data['target'] = $target['twitter_screen_name'];

      Mail::to($user['email'])
        ->send(new SendEmail($data));

      return false;
    }
  }