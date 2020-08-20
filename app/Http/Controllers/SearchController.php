<?php

namespace App\Http\Controllers;

use App\SearchKeywordList;
use Illuminate\Http\Request;

// 自動フォロー用キーワード処理コントローラー
class SearchController extends Controller
{
  /*
   * フォロー用キーワード(search_keyword_lists)作成用メソッド
   * リクエスト用パラメータを引数に取り、レスポンスを返します。
   *
   * @param $request フロント側のフォロー用キーワード情報
   * @return レスポンス
   */
  public function createSearchKeywordList(Request $request)
  {
    $arr = [];
    foreach($request->all() as $data) {
      if(array_key_exists('is_empty', $data)){
        // twitter_user_idの値を変数にセット
        $twitter_user_id = $data['twitter_user_id'];
        unset($data);

        break;

      }else {
        // messageプロパティを取り除く
        unset($data['message']);
        // optionsプロパティを取り除く
        unset($data['options']);

        // created_atとupdated_atを現在時刻として追加
        $data['created_at'] = now();
        $data['updated_at'] = now();

        // 配列arrに現在のループ配列dataを追加
        array_push($arr, $data);

        // twitter_user_idの値を変数にセット
        $twitter_user_id = $data['twitter_user_id'];
      }
    }

    // 同じtwitter_user_idのデータを一旦削除する
    $target = new SearchKeywordList;
    $target->OfTwitterUserId($twitter_user_id)->delete();

    // 配列の内容をDBへインサート
    if($arr){
      $target->insert($arr);
    }
    return $target;
  }

  /*
   * フォロー用キーワード(search_keyword_lists)参照用メソッド
   * リクエスト用パラメータを引数に取り、設定済みのフォロー用キーワードを返します。
   * フロント側で利用
   * @param $request Twitterユーザー情報
   * @return レスポンス
   */
  public function querySearchKeywordList(String $id)
  {
    $response = SearchKeywordList::OfTwitterUserId($id)
      ->select('selected', 'text')
      ->get();
    return $response;
  }

  /*
   * フォロー用Where句作成用メソッド
   * リクエスト用パラメータを引数に取り、フォロー用Where句を返す
   * @param $request Twitterユーザー情報
   * @return レスポンス
   */
  public function makeWhereConditions(String $id)
  {
    // サーチキーワードを配列形式で格納
    $arr = $this->querySearchKeywordList($id)->toArray();

    if(!$arr){
      // サーチキーワードの検索結果が空の場合はfalseを返す
      return false;
    }

    $converted_arr = [];  // 変換後の配列

    // AND, OR, NOTごとに配列$converted_arrにWHERE句を格納する
    foreach ($arr as $data){

      switch ($data['selected']){
        case 'AND':
          $converted_arr['AND'][] =  [$data['text']];
          break;
        case 'OR':
          $converted_arr['OR'][] =  [$data['text']];
          break;
        case 'NOT':
          $converted_arr['NOT'][] =  [$data['text']];
          break;
      }
    }
    return $converted_arr;
  }
}
