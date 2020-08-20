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
      $schedule->call('App\Http\Controllers\AutoTweetController@AutoTweet')
        ->everyMinute()
        ->name('task-tweet');

      $schedule->call('App\Http\Controllers\FavoriteController@autoFavorite')
        ->everyFifteenMinutes()
        ->name('task-favorite');

      $schedule->command('queue:work --tries=1 --stop-when-empty')
        ->everyMinute()
        ->withoutOverlapping(10);

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
