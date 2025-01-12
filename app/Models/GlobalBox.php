<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'times_played',
    ];

}
