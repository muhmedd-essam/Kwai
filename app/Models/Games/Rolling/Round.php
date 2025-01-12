<?php

namespace App\Models\Games\Rolling;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class Round extends Model
{
    use HasFactory;

    protected $fillable = ['start_time', 'end_time', 'number'];


    public function bets()
    {
        return $this->hasMany(Bet::class);
    }


    public function isOngoing()
    {
        $now = Carbon::now();
        return $now->between($this->start_time, $this->end_time);
    }

  
    public function timeRemaining()
    {
        return Carbon::now()->diffInSeconds($this->end_time, false);
    }
}
