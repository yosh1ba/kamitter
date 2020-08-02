<?php

namespace App\Http\Controllers\Auth;

use App\FollowedList;
use App\FollowerTargetList;
use App\Http\Controllers\FavoriteController;
use App\Mail\SendEmail;
use App\Reserve;
use App\TargetAccountList;
use App\TwitterUser;
use App\Http\Controllers\SearchController;
use App\UnfollowedList;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Abraham\TwitterOAuth\TwitterOAuth;
use phpDocumentor\Reflection\Types\Boolean;
use PhpParser\Node\Expr\Cast\Object_;
use PHPUnit\Util\Json;
use Psy\Util\Str;
use Ramsey\Uuid\Type\Integer;
use function Psy\debug;

/*
 * Twitter認証用クラス
*/
class TwitterController extends Controller
{

  // 認証ページへのリダイレクト用URLを返す
  public function redirectToProvider()
  {

    // 毎回認証画面を出すため、force_loginパラメータを付与
    $redirect = Socialite::driver('twitter')->redirect();
    $redirect->setTargetUrl($redirect->getTargetUrl() . '&force_login=true');

    // JSON形式でリダイレクトURLを返す
    return response()->json([
      'redirect_url' => $redirect->getTargetUrl(),
      // 'redirect_url' => Socialite::driver('twitter')->redirect()->getTargetUrl(),

    ]);
  }

  /*
   * Socialiteを使用して、Twitterログイン認証を行う
   * ログインに成功した場合、
   * 　すでにTwitterUsersテーブルに情報があれば、その情報を返す
   * 　無ければ、ユーザー情報を登録する
   * という処理を行う。
  */
  public function handleProviderCallback()
  {
    try {
      // twitterログイン認証
      $user = Socialite::driver('twitter')->user();
      // アクセストークン取得
      $token = $user->token;
      $token_secret = $user->tokenSecret;

      if ($user) {
        // ログイン中のユーザーID取得
        $user_id = Auth::id();

        /*
         twitter_userテーブルを検索する
          一致するものがあれば、データを返す
          一致するものがなければ、データを挿入する
        */
        $twitter_user = TwitterUser::firstOrCreate([
          'provider_user_id' => $user->id,  // twitterのユーザーID
          'user_id' => $user_id // ログイン中のユーザーID
        ]);

        $twitter_user->update(
          [
            'twitter_oauth_token' => $token,  // アクセストークン
            'twitter_oauth_token_secret' => $token_secret,  // アクセストークンシークレット
            'twitter_name' => $user->getName(), // Twitterユーザー名
            'twitter_screen_name' => $user->getNickname(),  // Twitterユーザー名(@マーク以降)
            'twitter_avatar' => str_replace('http', 'https', $user->getAvatar()), // アイコン(https化)
          ]
        );
      }
      return response()->json($user);

    } catch (Exception $e) {
      return false;
    }
  }

  /*
   * ベアラートークンを利用してTwitterAPIにアクセスするメソッドです。
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   *
   * @param $arr リクエスト用パラメータ
   * @param $request TwitterUsersテーブルのID
   * @return レスポンス
   */
  public function accessTwitterWithBearerToken(Array $arr, Request $request = null)
  {
    $url = $arr['url'];
    $params = $arr['params'];

    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン

    $response = Http::withToken($bearer_token)->get('https://api.twitter.com/1.1/'. $url, $params);

    // エラーが発生した場合、処理を停止する
    if(isset($response['errors'])){
      $data = [];
      $data['message'] =
        '自動処理が停止しました。
      アカウントをご確認下さい。
      ';
      $data['subject'] = '[神ったー]自動処理停止のお知らせ';
      $this->sendMail($request, $data);

      TwitterUser::find($request->route('id'))->update([
        'auto_pilot_enabled' => false,
        'pause_enabled' => false
      ]);
      exit();
    }
    return $response;
  }

  /*
   * アクセストークンを利用してTwitterAPIにアクセスするメソッドです。
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   *
   * @param $user TwitterUsersテーブル情報
   * @param $arr リクエスト用パラメータ
   * @param $context GET or POTの判定
   * @param $request TwitterUsersテーブルのID
   * @return レスポンス
   */
  public function accessTwitterWithAccessToken(Array $user, Array $arr,String $context = 'post',Request $request = null)
  {

    $client_id = config('app.twitter_client_id');;
    $client_secret = config('app.twitter_client_secret');
    $access_token = $user[0]['twitter_oauth_token'];
    $access_token_secret = $user[0]['twitter_oauth_token_secret'];

    $connection = new TwitterOAuth($client_id, $client_secret, $access_token, $access_token_secret);

    // GETかPOSTどちらかでアクセスする
    if($context === 'get'){
      $content = $connection->get($arr['url'],$arr['params']);
    }else{
      $content = $connection->post($arr['url'],$arr['params']);
    }

    // エラーが発生した場合、処理を停止する
    if(isset($content->errors)){
      $data = [];
      $data['message'] =
        '自動処理が停止しました。
      アカウントをご確認下さい。
      ';
      $data['subject'] = '[神ったー]自動処理停止のお知らせ';
      $this->sendMail($request, $data);

      TwitterUser::find($request->route('id'))->update([
        'auto_pilot_enabled' => false,
        'pause_enabled' => false
      ]);
      exit();
    }
    return $content;
  }

  /*
   * フォロワーターゲットリスト(FollowerTargetLists) 作成用メソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返します
   *
   * @param $request TwitterUsersテーブルのID
   * @param $screen_name Twitter表示名(@以降の名前)
   * @return レスポンス
   */
  public function createFollowerTargetList(Request $request, String $screen_name)
  {
    // APIリクエスト用のパラメータを定義
    $request_params = [];
    $request_params['url'] = 'followers/list.json';
    $request_params['params'] = [
      'cursor' => '-1',
      'count' => '200',
      'screen_name' => $screen_name
    ];

    $target = new FollowerTargetList();

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $request->route('id'))->delete();



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
        sleep(960);
      }

      /*
       * アプリケーション認証にてフォロワーリストを取得
       * $limit にAPIリクエスト可能回数を格納
       * $followers にフォロワーリストを格納
       */
      $response = $this->accessTwitterWithBearerToken($request_params);
      $limit = $response->header('x-rate-limit-remaining');
      $followers = $response['users'];

      foreach($followers as $follower){

        // 自分自身かどうかを判定
        $matchedMySelf = $this->judgeMatchedMySelf($request, $follower);

        // プロフィールに日本語が含まれるか判定
        $includedJapanese = $this->judgeIncludedJapanese($follower);

        // キーワードマッチングを行う
        $matchedKeywords = $this->judgeMatchedKeywords($request, $follower);

        // アンフォローリストに含まれるアカウントは除く
        $alreadyUnfollowed = $this->alreadyUnfollowed($request, $follower);

        // すでにフォロー済みのアカウントは除く
        $alreadyFollowed = $this->alreadyFollowed($request, $follower);

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
            'twitter_user_id' => $request->route('id'),
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
   * 自分自身のアカウントでないことを判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $user TwitterUsersテーブル情報
   * @param $request TwitterUsersテーブルのID
   * @return true or false
   */
  public function judgeMatchedMySelf(Request $request, Array $follower)
  {
    /*
     * twitter_usersテーブルから、自分自身の情報を取得
     * フォロワーリストのアカウントに自分自身がいるかどうかを判定
     *  いる場合    >>   true
     *  いない場合   >>  false（空）
     * を返す
    */
    $target = TwitterUser::find($request->route('id'));

    if($target->twitter_screen_name === $follower['screen_name']){
      return true;
    } else {
      return false;
    }
  }

  /*
   * プロフィールに日本語が含まれるかを判定するメソッド
   * フォロワー情報を引数に取り、真偽値を返す
   *
   * @param $arr Twitterカウント情報
   * @return true or false
   */
  public function judgeIncludedJapanese(Array $arr)
  {

    if(preg_match( '/[ぁ-ん]+|[ァ-ヴー]+|[一-龠]/u', $arr['description'])){
      return true;
    }
      return false;
  }

  /*
   * キーワード文がプロフィールに含まれるか判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $request TwitterUsersテーブルのID
   * @param $follower フォロワーリスト
   * @return true or false
   */
  public function judgeMatchedKeywords(Request $request, Array $follower){
    // サーチキーワードリストからwhere句を生成
    $search = new SearchController;
    $condition = $search->makeWhereConditions($request);

    $judgeAND = null;
    $judgeOR = null;
    $judgeNOT = null;

    // AND, OR, NOTそれぞれについて、判定を行う

    /*
     * ANDについて、
     * 一つでも一致しないものがある場合、その時点でfalseを返す
     */
    if(isset($condition['AND'])){
      foreach ($condition['AND'] as $data){
        if(strpos($follower['description'], $data[0]) === false){
          $judgeAND = false;
          break;
        } else{
          $judgeAND = true;
        }
      }
    }

    /*
     * ORについて、
     * 一つでも一致するものがある場合、trueを返す
     */
    if(isset($condition['OR'])){
      foreach ($condition['OR'] as $data){
        if(strpos($follower['description'], $data[0]) === false){
          $judgeOR = false;
        } else{
          $judgeOR = true;
          break;
        }
      }
    }

    /*
     * NOTについて、
     * 一つでも一致するものがある場合、falseを返す
     */
    if(isset($condition['NOT'])){
      foreach ($condition['NOT'] as $data){
        if(strpos($follower['description'], $data[0]) === false){
          $judgeNOT = false;
          break;
        } else{
          $judgeNOT = true;
        }
      }
    }

    /*
     * AND、OR条件どちらにも一致しない、または
     * NOT条件に一致する場合、マッチング結果はfalseとする
     */
    if( ($judgeAND === false && $judgeOR === false) || $judgeNOT === true ){
      return false;
    } elseif(($judgeAND === false && $judgeOR === null) || $judgeNOT === true){
      return false;
    } elseif(($judgeAND === null && $judgeOR === false) || $judgeNOT === true) {
      return false;
    } else {
      return true;
    }
  }

  /*
   * 30日以内にフォロー済みかを判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $request TwitterUsersテーブルのID
   * @param $follower フォロワーリスト
   * @return true or false
   */
  public function alreadyFollowed(Request $request, Array $follower)
  {

    // FollowedLists(フォロー済みリスト)の中で、30日以内にフォロー済みのカウントの場合はfalseを返す
    $target = FollowedList::where('twitter_user_id', $request->route('id'))
      ->where('screen_name', $follower['screen_name'])
      ->where('followed_at', '>', Carbon::now()->subMonth())
      ->get();
     if ($target->isEmpty()){
       return false;
     }else{
       return true;
     }
  }

  /*
   * アンフォロー済みかを判定するメソッド
   * Twitterアカウント情報とフォロワー情報を引数に取り、真偽値を返す
   *
   * @param $request TwitterUsersテーブルのID
   * @param $follower フォロワーリスト
   * @return true or false
   */
  public function alreadyUnfollowed(Request $request, Array $follower)
  {
    // UnfollowedLists(アンフォローリスト)に一致するものがあれば、falseを返す
    $target = UnfollowedList::where('twitter_user_id', $request->route('id'))
      ->where('screen_name', $follower['screen_name'])
      ->get();
    if ($target->isEmpty()){
      return false;
    }else{
      return true;
    }
  }

  /*
   * 対象のツイッターアカウントが有効がどうかを判定するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   *
   * @param $request Twitter表示名(@以降の名前)
   * @return レスポンス
   */
  public function checkTargetAccountList(Request $request)
  {
    $bearer_token = config('app.twitter_bearer_token');  // ベアラートークン
    $request_url = 'https://api.twitter.com/1.1/users/show.json' ;  // エンドポイント

    $response = Http::withToken($bearer_token)->get(
      $request_url, [
        'screen_name' => $request->screen_name
      ]
    );
    // Log::debug($response->json());

    return json_decode($response,true);
  }

  /*
   * TargetAccountLists(ターゲットアカウントリスト)を作成するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   * @param $request Twitter表示名(@以降の名前)
   * @return レスポンス
   */
  public function createTargetAccountList(Request $request)
  {

    // フロント側でターゲットアカウントが空の場合h処理をスキップする
    if( empty($request->all())  ){
      return false;
    }

    $arr = [];
    foreach($request->all() as $data) {

      // messageプロパティを取り除く
      unset($data['message']);

      // created_atとupdated_atを現在時刻として追加
      $data['created_at'] = now();
      $data['updated_at'] = now();

      // 配列arrに現在のループ配列dataを追加
      array_push($arr, $data);

      // twitter_user_idの値を変数にセット
      $twitter_user_id = $data['twitter_user_id'];

    }

    $target = new TargetAccountList;

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $twitter_user_id)->delete();

    // 配列の内容をDBへインサート
    $target->insert($arr);

    return $target;
  }

  /*
   * FollowedLists(フォロー済みリスト)を作成するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   * @param $request TwitterUsersテーブルのID
   * @param $obj フォロー済みアカウントの情報
   * @return レスポンス
   */
  public function createFollowedLists(Request $request, Object $obj){
    $follower = FollowedList::create([
      'user_id' => $obj->id,
      'screen_name' => $obj->screen_name,
      'twitter_user_id' => $request->route('id')
    ]);
    return $follower;
  }

  /*
   * UnFollowedLists(アンフォロー済みリスト)を作成するメソッド
   * Twitterアカウント情報を引数に取り、レスポンスを返す
   * @param $request TwitterUsersテーブルのID
   * @param $obj アンフォロー済みアカウントの情報
   * @return レスポンス
   */
  public function createUnfollowedLists(Request $request, Object $obj){
    $unfollow = UnfollowedList::create([
      'user_id' => $obj->id,
      'screen_name' => $obj->screen_name,
      'twitter_user_id' => $request->route('id')
    ]);
    return $unfollow;
  }

  /*
   * FollowerTargetLists(フォロワーターゲットリスト)更新用メソッド
   * Twitterアカウント情報とTwitter表示名を引数に取り、フォロー済み判カラム(is_followed)をtrueにする
   * @param $request TwitterUsersテーブルのID
   * @param $obj アンフォロー済みアカウントの情報
   * @return なし
   */
  public function updateFollowerTargetList(Request $request, String $screen_name){
    $target = new FollowerTargetList();

    $target->where('twitter_user_id', $request->route('id'))
      ->where('screen_name', $screen_name)
      ->update(['is_followed' => true]);
  }

  /*
   * Reserves(予約ツイート)更新用メソッド
   * 予約ツイートIDを引数に取り、投稿済み判定用カラム(is_posted)をtrueにする
   * @param $id 予約ツイートID
   * @return なし
   */
  public function updateReserves(String $id){
    $target = new Reserve();

    $target->where('id', $id)
      ->update(['is_posted' => true]);
  }

  /*
   * ログイン済みユーザーに紐づくTwitterUsers情報参照用メソッド
   * ユーザー情報を引数に取り、それに紐づくTwitterUsersテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  ユーザー情報(サービス側)
   * @return レスポンス
   */
  public function queryLinkedUsers(Request $request)
  {

    // ログインしているユーザーに紐付くtwitter_usersテーブル情報を返す
    return TwitterUser::where('user_id', $request->route('id'))->get();
  }

  /*
   * Twitterユーザー情報に紐づくターゲットアカウント情報参照用メソッド
   * ユーザー情報を引数に取り、それに紐づくTargetAccountListsテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryTargetAccountList(Request $request)
  {

    $response = TargetAccountList::where('twitter_user_id', $request->route('id'))->select('screen_name')->get();
    return $response;
  }

  /*
   * Twitterユーザー情報に紐づくフォロワーターゲットリスト参照用メソッド
   * ユーザー情報を引数に取り、それに紐づくFollowerTargetListsテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryFollowerTargetList(Request $request)
  {

    $response = FollowerTargetList::where('twitter_user_id', $request->route('id'))
      ->where('is_followed', false)
      ->select('screen_name')->get();
    return $response;
  }

  /*
   * Twitterユーザー情報参照用メソッド
   * TwitterUsersテーブルのIDを引数に取り、テーブルの情報を返す
   * トークンの取得に利用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryAuthenticatedUser(Request $request)
  {

    $response = TwitterUser::where('id', $request->route('id'))->select(
      'twitter_screen_name',
      'twitter_oauth_token',
      'twitter_oauth_token_secret'
    )->get();
    return $response;
  }

  /*
   * Twitterユーザー情報参照用メソッド
   * TwitterUsersテーブルのIDを引数に取り、テーブルの情報を返す
   * queryAuthenticatedUserメソッドの引数が文字列だった場合のメソッド
   * トークンの取得に利用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryAuthenticatedUserAsString(String $id)
  {

    $response = TwitterUser::where('id', $id)->select(
      'twitter_screen_name',
      'twitter_oauth_token',
      'twitter_oauth_token_secret'
    )->get();
    return $response;
  }

  /*
   * 自動運用判定用メソッド
   * TwitterUsersテーブルのIDを引数に取り、自動運用が有効かどうか(auto_pilot_enabled)を返す
   * @param $request  Twitterアカウント情報
   * @return true or false
   */
  public function judgeAutoPilot(Request $request)
  {
    $target = TwitterUser::find($request->route('id'));

    if($target->auto_pilot_enabled === 1){
      return true;
    } else {
      return false;
    }
  }

  /*
   * 一時停止判定用メソッド
   * TwitterUsersテーブルのIDを引数に取り、一時停止が有効かどうか(pause_enabled)を返す
   * @param $request  Twitterアカウント情報
   * @return true or false
   */
  public function judgePaused(Request $request)
  {
    $target = TwitterUser::find($request->route('id'));

    if($target->pause_enabled === 1){
      return true;
    } else {
      return false;
    }
  }

  /*
   * アンフォロー対象ユーザー参照用メソッド
   * Twitterアカウント情報とTwitter表示名を引数に取り、
   * フォロー済みのTwitterユーザーの中で、フォロー返しの無いユーザーを返す
   * @param $request TwitterUsersテーブルのID
   * @param $screen_name  Twitter表示名(@以降の名前)
   * @return ユーザー情報
   */
  public function queryUnfollowTargetList(Request $request, String $screen_name)
  {
    $request_params = [];
    $request_params['url'] = 'friends/ids.json';  // フォロー済みのTwitterID
    $request_params['params'] = [
      'cursor' => '-1',
      'count' => '5000',
      'stringify_ids' => true,
      'screen_name' => $screen_name
    ];
    $friends = $this->accessTwitterWithBearerToken($request_params, $request)['ids'];

    $request_params['url'] = 'followers/ids.json';  // フォロワーのTwitterID
    $followers = $this->accessTwitterWithBearerToken($request_params, $request)['ids'];

    // フォロー返しの無いTwitterユーザー情報を格納
    $oneways = array_diff($friends, $followers);

    return $oneways;

  }

  /*
   * フォローから一定日数以上経過したユーザー情報を返す
   * Twitterユーザー情報と日数を引数に取り、フォローから対象日数以上が経過したユーザーを返す
   * @param $request Twitterユーザー情報
   * @param $day  判定用日数
   * @return ユーザー情報
   */
  public function queryFollowedLists(Request $request, Int $day)
  {
    $response = FollowedList::where('twitter_user_id', $request->route('id'))
      ->where('followed_at', '<', Carbon::now()->subDay($day))
      ->select('user_id')
      ->get();

    return $response;
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
    $request_params = [];
    $request_params['url'] = 'friends/ids.json';  // フォローリスト取得
    $request_params['params'] = [
      'cursor' => '-1',
      'count' => '5000',
      'stringify_ids' => true,
      'screen_name' => $screen_name
    ];
    $friends = $this->accessTwitterWithBearerToken($request_params, $request)['ids'];

    $request_params = [];
    $request_params['url'] = 'statuses/user_timeline.json'; // ユーザータイムライン取得
    $request_params['params'] = [
      'count' => '1',
      'user_id' => ''
    ];

    $inactive_users = [];
    /*
     * フォロー済みの各ユーザーに対して、$day日間以内にツイートがあるかを判定する
     */
    foreach ($friends as $friend){
      $request_params['params']['user_id'] = $friend;

      $timeline = $this->accessTwitterWithBearerToken($request_params, $request);

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

    $response = $this->accessTwitterWithAccessToken($decoded_user, $request_params, 'get', $request);

    // フォロー数を返す
    return $response->friends_count;

  }

  /*
   * 予約ツイート情報取得用メソッド
   * ユーザー情報を引数に取り、それに紐づくReservesテーブルの情報を返す
   * フロント側での画面描画に使用する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function queryReserve(Request $request){
    $response = Reserve::where('twitter_user_id', $request->route('id'))
      ->where('is_posted', false) // 未投稿を対象とする
      ->get();
    return $response;
  }

  /*
   * 自動運用メソッド
   * ユーザー情報を引数に取り、自動運用を行う
   * 　・自動フォロー
   * 　・自動アンフォロー（フォロー数が5000を超えた場合)
   * 　・自動いいね（スキマ時間）
   * これらの処理を一連で行う
   * @param $request  Twitterアカウント情報
   * @param $restart  一時停止から再開した場合の判定
   * @return 最終のレスポンス
   */
  public function autoFollow(Request $request,$restart = null)
  {

    // 自動運用判定用カラム(auto_pilot_enabled)をtrueにする
    TwitterUser::find($request->route('id'))->update([
      'auto_pilot_enabled' => true
    ]);

    // 認証済みアカウント情報取得
    $user = $this->queryAuthenticatedUser($request);

    // ターゲットアカウントリスト取得
    $lists = $this->queryTargetAccountList($request);

    // 1ターゲットアカウント毎に自動処理を行う
    foreach ($lists as $list){

      // リスタートで無い場合は、フォロワーターゲットリストを作成する
      if ($restart !== true){
        $response = $this->createFollowerTargetList($request, $list->screen_name);
      }

      // 未フォローのフォロワーターゲットリストを取得
      $targets = $this->queryFollowerTargetList($request);


      $request_params = [];
      $request_params['url'] = 'friendships/create';
      $request_params['params'] = [
        'screen_name' => ''
      ];
      $count = 0; // APIリクエスト可能回数

      /*
       * フォロワーターゲットリストのアカウントを順番にフォローしていく。
       * 以下の通り、ウェイトを設ける。
       *  ・１アカウントフォローするごとに15秒
       *  ・15アカウントフォローするごとに16分（この間に自動いいねを行う）
       * また、5000フォローを超えた場合、自動アンフォローを開始する
       *
       * twitterAPIからエラーが返ってきた場合、その時点で処理を終了する
      */
      foreach ($targets as $target){
        // 自動処理無効もしくは一時停止の場合、処理を中止する
        if($this->judgeAutoPilot($request) === false || $this->judgePaused($request) === true){
          return false;
        }

        $request_params['params']['screen_name'] = $target->screen_name;

        // 現在のフォロー数を確認する
        $friends_count = $this->queryFriendsCount($request ,$user);

        // フォロー数が5000人を超えた場合、自動アンフォローを開始する
        if($friends_count >= 5000){
          if ($restart !== true){
            $this->autoUnfollow($request);
          }else {
            $this->autoUnfollow($request, true);
          }
        }

        // 15カウントごとにカウントごとに16分(960秒)待機する
        // このタイミングで自動いいねを行う
        if( $count !== 0  && ($count % 15) === 0 ){
          $this->autoFavorite($request);
          sleep(960);
        }
        // twitterAPIへフォローリクエストを送る
        $response = $this->accessTwitterWithAccessToken(json_decode($user, true), $request_params, $request);

        // アカウント情報が返ってこない（エラーが発生した）場合、処理を中断する
        if(!property_exists($response, 'id')){
          return false;
        }else {
          // フォロー成功した場合、フォロー済みリストにもアカウント情報を格納する
          $this->updateFollowerTargetList($request, $target->screen_name);
          $this->createFollowedLists($request, $response);
        }
        sleep(15);
        $count++;
      }
    }
    // 自動処理が完了した後に完了メールを送信する
    $data = [];
    $data['message'] =
      '自動処理が完了しました。
      ご確認をお願い致します。
      ';
    $data['subject'] = '[神ったー]自動処理完了のお知らせ';
    $this->sendMail($request, $data);

    TwitterUser::find($request->route('id'))->update([
      'auto_pilot_enabled' => false,
      'pause_enabled' => false
    ]);
    return response()->json($response);
  }

  /*
   * 自動アンフォロー用メソッド
   * ユーザー情報を引数に取り、自動アンフォローを行う
   * @param $request  Twitterアカウント情報
   * @param $restart  一時停止から再開した場合の判定
   * @return なし
   */
  public function autoUnfollow(Request $request, $restart = null)
  {

    $user = TwitterUser::find($request->route('id'));

    // 再開出ない場合、アンフォローターゲットリストを取得
    if ($restart === null) {
      $oneways = $this->queryUnfollowTargetList($request, $user['twitter_screen_name']);
    }

    // フォロー後に7日経過してもフォロー返しがないユーザを$while_ago_followに格納する
    $followed_lists = $this->queryFollowedLists($request, 7);
    $while_ago_follow = [];
    foreach ($followed_lists as $friend) {
      array_push($while_ago_follow, $friend->user_id);
    }

    // 片思い中のアカウントと($oneways)、7日間フォロー返しがないアカントをマッチング
    $targets = array_intersect($oneways, $while_ago_follow);

    // 15日間ツイートがないアカウントを$inactive_usersに格納
    $inactive_users = $this->queryInactiveUsers($request, $user['twitter_screen_name'], 15);

    // $targetsと$inactive_usersで共通するアカウントを$merge_targetsに格納
    // 重複を削除
    $merge_targets = array_merge($targets, $inactive_users);
    $unique_targets = array_unique($merge_targets);

    // キー番号を振り直し
    $result_targets = array_values($unique_targets);

    $request_params = [];
    $request_params['url'] = 'friendships/destroy';
    $request_params['params'] = [
      'id' => ''
    ];

    $user = $this->queryAuthenticatedUser($request);

    /*
     * $result_targetsの全てについて、アンフォローを実施
     * 1アンフォローごとに15秒間隔を開ける
     */
    foreach ($result_targets as $target) {
      // 自動処理無効もしくは一時停止の場合、処理を中止する
      if($this->judgeAutoPilot($request) === false || $this->judgePaused($request) === false){
        return false;
      }

      $request_params['params']['id'] = $target;
      $response = $this->accessTwitterWithAccessToken(json_decode($user, true), $request_params, $request);

      // アカウント情報が返ってこない（エラーが発生した）場合、処理を中断する
      if (!property_exists($response, 'id')) {
        return false;
      } else {
        $this->createUnfollowedLists($request, $response);
      }
      sleep(15);

    }
    return false;
  }

  /*
   * 自動いいね用メソッド
   * ユーザー情報を引数に取り、自動いいねを行う
   * @param $request  Twitterアカウント情報
   * @return 無し
   */
  public function autoFavorite(Request $request)
  {
    $user = $this->queryAuthenticatedUser($request);

    // いいね用キーワード取得
    $favorite = new FavoriteController;
    $condition = $favorite->makeWhereConditions($request);

    // いいね用キーワードがない場合、処理をスキップする
    if($condition === false){
      return false;
    }
    $query = '';

    // AND, OR, NOTそれぞれを用いて検索用クエリを作成する
    if(isset($condition['AND'])){
      $query = '(';
      foreach ($condition['AND'] as $data){
        $query = $query .' '. $data[0];
      }
      $query = $query. ')';
    }

    if(isset($condition['OR'])){
      foreach ($condition['OR'] as $data){
        $query = $query .' OR '. $data[0];
      }
    }

    if(isset($condition['NOT'])){
      foreach ($condition['NOT'] as $data){
        $query = $query .' -'. $data[0];
      }
    }

    $query = $query . ' exclude:retweets -filter:replies';  // リツイート、リプライを除く

    $request_params = [];
    $request_params['url'] = 'search/tweets'; // ツイート検索
    $request_params['params'] = [
      'q' => $query,
      'count' => 10,
      'result_type' => 'recent'
    ];

    // ツイートを検索する
    $response = $this->accessTwitterWithAccessToken(json_decode($user, true), $request_params, 'get', $request);

    $request_params = [];
    $request_params['url'] = 'favorites/create';  // いいねを付ける
    $request_params['params'] = [
      'id' => '',
    ];

    foreach ($response->statuses as $tweet){
      // 自動処理無効もしくは一時停止の場合、処理を中止する
      if($this->judgeAutoPilot($request) === false || $this->judgePaused($request) === true){
        return false;
      }
      $request_params['params']['id'] = $tweet->id;
      $response = $this->accessTwitterWithAccessToken(json_decode($user, true), $request_params, $request);
      sleep(10);  // 10秒待機する
    }
    return false;
  }

  /*
   * 予約ツイート設定用メソッド
   * ユーザー情報を引数に取り、予約ツイートを設定する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function reserveTweet(Request $request)
  {
    // フォームから予約ツイート内容と予約時間を取得
    $tweet = [];
    $tweet = $request->all();

    // DBへ格納
    // すでに登録されている場合は上書き
    $response = Reserve::updateOrCreate(
      ['twitter_user_id' => $tweet['twitter_user_id']],
      [
        'reserved_at' => date('Y-m-d H:i:s', strtotime($tweet['reserved_at'])),
        'tweet' => $tweet['tweet'],
        'is_posted' => false
      ]
    );

    return response()->json($response);
  }

  /*
   * 予約ツイート投稿用メソッド
   * 予約ツイートを投稿する
   * @return なし
   */
  public function autoTweet()
  {

    // 現在時刻をキーに、reserveテーブルを検索
    // is_postedがfalseの値を探す
    $reserves = Reserve::whereBetween('reserved_at', [Carbon::now()->subSeconds(30), Carbon::now()->addSeconds(30)])
      ->where('is_posted',false)
      ->get();

    if($reserves->count() === 0){
      return false;
    }
    $request_params = [];
    $request_params['url'] = 'statuses/update';
    $request_params['params'] = [
      'status' => '',
    ];

    // 該当した予約ツイートについて投稿を行う
    foreach ($reserves as $reserve){
      $request_params['params']['status'] = $reserve['tweet'];
      $user = $this->queryAuthenticatedUserAsString($reserve['twitter_user_id']);
      $response = $this->accessTwitterWithAccessToken(json_decode($user, true), $request_params, $reserve['twitter_user_id']);

      // 投稿済み判定用カラムを変更する
      $this->updateReserves($reserve['id']);

    }
  }

  /*
   * 認証済みユーザー解除用メソッド
   * ユーザー情報を引数に取り、対象の認証済みユーザーの認証を解除する
   * @param $request  Twitterアカウント情報
   * @return レスポンス
   */
  public function deleteAuthenticatedUser(Request $request)
  {

    $response = TwitterUser::where('id', $request->route('id'))
      ->delete();
    return $response;
  }

  /*
   * 一時停止メソッド
   * ユーザー情報を引数に取り、一時停止判定用カラムを変更する
   * @param $request  Twitterアカウント情報
   * @return なし
   */
  public function toPause(Request $request)
  {
    TwitterUser::find($request->route('id'))->update([
      'pause_enabled' => true
    ]);

    return false;
  }


  /*
   * 再開メソッド
   * ユーザー情報を引数に取り、一時停止判定用カラムを変更し、自動処理を再開する
   * @param $request  Twitterアカウント情報
   * @return なし
   */
  public function toRestart(Request $request)
  {
    TwitterUser::find($request->route('id'))->update([
      'pause_enabled' => false
    ]);
    // 自動処理再開
    $this->autoFollow($request, (bool)TRUE);

    return false;
  }

  /*
   * 自動処理停止用メソッド
   * ユーザー情報を引数に取り、
   * 　・自動処理判定用カラム
   * 　・一時停止判定用カラム
   * を変更し、自動処理を停止する
   * @param $request  Twitterアカウント情報
   * @return なし
   */
  public function toCancel(Request $request)
  {
    TwitterUser::find($request->route('id'))->update([
      'auto_pilot_enabled' => false,
      'pause_enabled' => false
    ]);

    return false;
  }

  /*
   * メール送信準備用メソッド
   * ユーザー情報とメール送信用情報を引数に取り、メールを送信用メソッドを読み出す
   * @param $request  Twitterアカウント情報
   * @param $data メール送信用情報
   * @return なし
   */
  public function sendMail(Request $request, Array $data)
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
}
