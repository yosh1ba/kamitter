<?php

namespace App\Http\Controllers;

use App\SearchKeywordList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
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

    $target = new SearchKeywordList;

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $twitter_user_id)->delete();

    if($arr){
      // 配列の内容をDBへインサート
      $target->insert($arr);
    }



    Log::debug($target);

    return $target;
  }

  public function querySearchKeywordList(Request $request)
  {
    $response = SearchKeywordList::where('twitter_user_id', $request->route('id'))->select('selected', 'text')->get();

    return $response;
  }

  public function makeWhereConditions(Request $request)
  {
    // サーチキーワードを配列形式で格納
    $arr = $this->querySearchKeywordList($request)->toArray();

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
