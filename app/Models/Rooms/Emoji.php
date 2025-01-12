<?php

namespace App\Models\Rooms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emoji extends Model
{
    use HasFactory;

    protected $fillable = [
        'cover',
        'body',
    ];

    public function getCoverAttribute($value){
        return asset('storage/' . $value);
    }

    public function getBodyAttribute($value){
        return asset('storage/' . $value);
    }
}
