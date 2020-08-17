<?php


  namespace App\Library;


  use App\Http\Controllers\Auth\TwitterController;
  use Carbon\Carbon;
  use Illuminate\Http\Request;

  // フレンドシップ関連クラス
  class Friendship
  {
    /*
     * フォロー数カウント用メソッド
     * Twitterユーザー情報とTwitter表示名、日数を引数に取り、
     * 一定日数アクティブでない(ツイートが無い)ユーザー情報を返す
     * @param $request Twitterユーザー情報
     * @param $$user TwitterUsersテーブル情報
     * @return ユーザー情報
     */
    public function queryFriendsCount(Request $request, Object $user)
    {
      $decoded_user = json_decode($user, true);

      $request_params = [];
      $request_params['url'] = 'users/show';  // ユーザ情報取得
      $request_params['params'] = [
        'screen_name' => $decoded_user[0]['twitter_screen_name']
      ];

      $twitter_controller = new TwitterController;
      $response = $twitter_controller->accessTwitterWithAccessToken($decoded_user, $request_params, 'get', $request);

      // フォロー数を返す
      return $response->friends_count;

    }


    /*
     * 一定日数アクティブでないユーザーの参照用メソッド
     * Twitterユーザー情報とTwitter表示名、日数を引数に取り、
     * 一定日数アクティブでない(ツイートが無い)ユーザー情報を返す
     * @param $request Twitterユーザー情報
     * @param $screen_name Twitter表示名(@以降の名前)
     * @param $day  判定用日数
     * @return ユーザー情報
     */
    public function queryInactiveUsers(Request $request, String $screen_name, Int $day)
    {
      $twitter_controller = new TwitterController;

      $request_params = [];
      $request_params['url'] = 'friends/ids.json';  // フォローリスト取得
      $request_params['params'] = [
        'cursor' => '-1',
        'count' => '5000',
        'stringify_ids' => true,
        'screen_name' => $screen_name
      ];
      $friends = $twitter_controller->accessTwitterWithBearerToken($request_params, $request)['ids'];

      $request_params = [];
      $request_params['url'] = 'statuses/user_timeline.json'; // ユーザータイムライン取得
      $request_params['params'] = [
        'count' => '1',
        'user_id' => ''
      ];

      $inactive_users = [];

      // フォロー済みの各ユーザーに対して、$day日間以内にツイートがあるかを判定する
      foreach ($friends as $friend){
        $request_params['params']['user_id'] = $friend;

        $timeline = $twitter_controller->accessTwitterWithBearerToken($request_params, $request);

        /*
         * $datetime_tweet  最新のツイート日時
         * $datetime_past   現在時刻から$day日前の日時
         */
        if(isset($timeline->json()[0]['created_at'])){
          $datetime_tweet = date('Y-m-d H:i:s', strtotime($timeline->json()[0]['created_at']));
          $datetime_past = Carbon::now()->subDay($day);

          // 最新のツイート日時のほうが古い場合、$inactive_usersに格納
          if ($datetime_tweet < $datetime_past){
            array_push($inactive_users, $friend);
          }
        }
      }
      return $inactive_users;
    }
  }