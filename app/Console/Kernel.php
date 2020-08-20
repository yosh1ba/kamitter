<?php

namespace App\Console;

use App\Http\Controllers\Auth\TwitterController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
      // 毎分毎に自動ツイートメソッドを実行する
      $schedule->call('App\Http\Controllers\AutoTweetController@AutoTweet')
        ->everyMinute()
        ->name('task-tweet');

      // 15分間隔で自動いいねメソッドを実行する
      $schedule->call('App\Http\Controllers\FavoriteController@autoFavorite')
        ->everyFifteenMinutes()
        ->name('task-favorite');

      // 毎分毎にキューを実行する(自動フォローメソッドで利用)
      $schedule->command('queue:work --tries=1 --timeout=960 --stop-when-empty')
        ->everyMinute();

      // 15分毎に失敗ジョブを自動削除する
      $schedule->command('queue:flush')
        ->everyFifteenMinutes()
        ->withoutOverlapping(10);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
