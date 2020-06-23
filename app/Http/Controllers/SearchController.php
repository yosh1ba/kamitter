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

    $target = new SearchKeywordList;

    // 同じtwitter_user_idのデータを一旦削除する
    $target->where('twitter_user_id', $twitter_user_id)->delete();

    // 配列の内容をDBへインサート
    $target->insert($arr);

    return $target;
  }

  public function querySearchKeywordList(Request $request)
  {
    $response = SearchKeywordList::where('twitter_user_id', $request->route('id'))->select('selected', 'text')->get();

    return $response;
  }

}
