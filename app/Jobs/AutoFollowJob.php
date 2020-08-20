<?php

namespace App\Jobs;

use App\Http\Controllers\FollowController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoFollowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;
    private $restart;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Request $request, $restart)
    {
      Log::debug('AutoFollowJob__construct In');
      Log::debug('id: '. $request->route('id'));
      Log::debug('restart: '. $restart);

      $this->request = $request->route('id');
      $this->restart = $restart;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      Log::debug('AutoFollowJob In');
      Log::debug('id: '. $this->request);
      Log::debug('restart: '. $this->restart);
        $follow_controller = new FollowController();

        $follow_controller->autoFollow($this->request, $this->restart);
    }
}
