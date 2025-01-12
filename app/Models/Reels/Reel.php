<?php

namespace App\Models\Reels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Reels\ReelLike;
use App\Models\Reels\ReelComment;

class Reel extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'description',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(ReelLike::class);
    }

    public function comments()
    {
        return $this->hasMany(ReelComment::class);
    }

    public function getPathAttribute($value)
    {
        return asset('/storage/' . $value);
    }
    
}
