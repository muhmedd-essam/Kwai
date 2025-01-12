<?php

namespace App\Models\Games;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    
    protected $table = 'transactions';

    protected $fillable = [
        'order_id', 'game_id', 'round_id', 'uid', 'coin', 'type', 'token', 'sign'
    ];
}
