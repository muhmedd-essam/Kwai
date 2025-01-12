<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PhotoAlbum extends Model
{
    use HasFactory;
    protected $fillable = [
        'path',
        'order',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPathAttribute($value){
        return asset('/storage/' . $value);
    }

}
