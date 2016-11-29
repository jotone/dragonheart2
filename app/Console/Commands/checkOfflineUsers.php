<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class checkOfflineUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*Every 4 minutes*/
        /*Online counters*/
        $date = date('Y-m-d H:i:s', time()-240);
        $users_offline = \DB::table('users')->select('id','user_online','updated_at')
            ->where('user_online','>',0)
            ->where('updated_at','<',$date)
            ->update(['user_online' => 0]);
    }
}
