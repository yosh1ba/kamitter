<?php


  namespace App\Library;


  use App\FollowerTargetList;
  use App\Http\Controllers\Auth\TwitterController;
  use App\Http\Controllers\JudgeController;
  use Illuminate\Http\Request;

  class Target
  {
    /*
     * フォロワーターゲットリスト(FollowerTargetLists) 作成用メソッド
     * Twitterアカウント情報を引数に取り、レスポンスを返します
     *
     * @param $request TwitterUsersテーブルのID
     * @param $screen_name Twitter表示名(@以降の名前)
     * @param 各コントローラのメソッドインジェクション
     * @return レスポンス
    */
    public function createFollowerTargetList(String $id, String $screen_name)
    {
      // APIリクエスト用のパラメータを定義
      $request_params = [];
      $request_params['url'] = 'followers/list.json';
      $request_params['params'] = [
        'cursor' => '-1',
        'count' => '200',
        'screen_name' => $screen_name
      ];

      $target = new FollowerTargetList;

      // 同じtwitter_user_idのデータを一旦削除する
      $target->OfTwitterUserId($id)->delete();

      $count = 1; // ループ回数カウント
      $limit = '1'; // APIリクエスト可能回数

      /*
        ターゲットアカウントに対して
        1. フォロワーを取得
        2. 各種条件で絞り込み
          ・自分自身でない
          ・プロフィールに日本語が含まれる
          ・プロフィールとサーチキーワードの条件が一致
          ・アンフォローリストのアカウントは除く
          ・30日以内にフォロー済みのアカウントは除く
        3. 結果をフォロワーターゲットリストに格納
      */
      do {
        /*
         * 15回目を実行する前に待機時間を作る
         * API制限状は15分(900秒)だが、念の為16分(960秒)を待機時間として設定する
        */
        if( $limit === '0' ){
          WaitProcess::wait($id);
        }

        /*
         * アプリケーション認証にてフォロワーリストを取得
         * $limit にAPIリクエスト可能回数を格納
         * $followers にフォロワーリストを格納
         */
        $twitter_controller = new TwitterController;
        $response = $twitter_controller->accessTwitterWithBearerToken($request_params);
        $limit = $response->header('x-rate-limit-remaining');
        $followers = $response['users'];

        foreach($followers as $follower){
          $judge_controller = new JudgeController;

          // 自分自身かどうかを判定
          $matchedMySelf = $judge_controller->judgeMatchedMySelf($id, $follower);

          // プロフィールに日本語が含まれるか判定
          $includedJapanese = $judge_controller->judgeIncludedJapanese($follower);

          // キーワードマッチングを行う
          $matchedKeywords = $judge_controller->judgeMatchedKeywords($id, $follower);

          // アンフォローリストに含まれるアカウントは除く
          $alreadyUnfollowed = $judge_controller->alreadyUnfollowed($id, $follower);

          // すでにフォロー済みのアカウントは除く
          $alreadyFollowed = $judge_controller->alreadyFollowed($id, $follower);

          /*
           * ・自分自身でないこと
           * ・プロフィールに日本語が含まれること
           * ・キーワードとマッチすること
           * ・30日以内にフォロー済みでないこと
           * ・アンフォロー済みでないこと
           * これらの条件を満たしている場合に、フォロワーターゲットリスト追加用キューに格納する
           * */
          if($matchedMySelf === false && $includedJapanese && $matchedKeywords && $alreadyFollowed === false && $alreadyUnfollowed === false){

            $followerQueue[] = [
              'screen_name' => $follower['screen_name'],
              'is_followed' => false,
              'twitter_user_id' => $id,
              'created_at' => now(),
              'updated_at' => now()
            ];
          }
        }

        // キューが存在する場合、フォロワーリストへアカウントを追加する
        if (isset($followerQueue)){
          $target->insert($followerQueue);
          unset($followerQueue);
          unset($alreadyFollowed);
        }
        if (isset($followers)){
          unset($followers);
        }
        $count++;
      }while ($request_params['params']['cursor'] = $response['next_cursor_str']);  // 全フォロワー分繰り返す

      return $response;
    }


    /*
     * Twitterユーザー情報に紐づくフォロワーターゲットリスト参照用メソッド
     * ユーザー情報を引数に取り、それに紐づくFollowerTargetListsテーブルの情報を返す
     * フロント側での画面描画に使用する
     * @param $request  Twitterアカウント情報
     * @return レスポンス
     */
    public function queryFollowerTargetList(String $id)
    {
      return FollowerTargetList::OfTwitterUserId($id)
        ->NotFollowed()
        ->select('screen_name')
        ->get();
    }


    /*
     * FollowerTargetLists(フォロワーターゲットリスト)更新用メソッド
     * Twitterアカウント情報とTwitter表示名を引数に取り、フォロー済み判カラム(is_followed)をtrueにする
     * @param $request TwitterUsersテーブルのID
     * @param $obj アンフォロー済みアカウントの情報
     * @return なし
     */
    public function updateFollowerTargetList(String $id, String $screen_name){

      FollowerTargetList::OfTwitterUserId($id)
        ->OfScreenName($screen_name)
        ->update(['is_followed' => true]);

    }
  }