<?php

namespace App\Models\Reels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Reels\Reel;

class ReelLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_id',
        'user_id',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }

}
