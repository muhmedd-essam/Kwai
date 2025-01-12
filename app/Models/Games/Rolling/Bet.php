<?php

namespace App\Models\Games\Rolling;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    use HasFactory;
    
    protected $table = 'betsrolling';

    protected $fillable = ['user_id', 'round_id', 'amount', 'number', 'multiplier'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
