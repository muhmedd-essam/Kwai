<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Following extends Model
{
    use HasFactory;

    protected $fillable = [
        'following_id',
        'follower_id',
    ];

    public function followingUser()
    {
        return $this->belongsTo(User::class, 'following_id');
    }

    public function followerUser()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }
    
}
