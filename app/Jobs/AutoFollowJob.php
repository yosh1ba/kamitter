<?php

namespace App\Jobs;

use App\Http\Controllers\FollowController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// 自動フォロー用ジョブクラス
class AutoFollowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;
    private $restart;

    /**
     * ジョブがタイムアウトになるまでの秒数
     *
     * @var int
     */
    public $timeout = 960;

    // リクエスト情報(TwitterUsersテーブルのID)とリスタート判定を引数に取る
    public function __construct(Request $request, $restart)
    {
      $this->request = $request->route('id');
      $this->restart = $restart;
    }


    // コンストラクタで受け取った引数をもとに、自動フォローを実施する
    public function handle()
    {
        $follow_controller = new FollowController();
        $follow_controller->autoFollow($this->request, $this->restart);
    }
}
