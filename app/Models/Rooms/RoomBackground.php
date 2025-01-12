<?php

namespace App\Models\Rooms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'is_free',
        'price',
        'room_id'
    ];

    public function getPathAttribute($value){
        return asset('storage/' . $value);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

}
