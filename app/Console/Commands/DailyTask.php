<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\DailyNotification;

class DailyTask extends Command
{
    protected $signature = 'daily:task';

    protected $description = 'Perform daily tasks';

    public function handle()
    {
        $users = User::all();
        
        foreach ($users as $user) {
            if ($user->vip_time > 0){
                $user->vip_time -=1;
                $user->vip_time;
                $user->save();
            }else{
                $user->vip = null;
                $user->save();
            }
        }

        $this->info('Daily task has been performed successfully.');
    }
}
