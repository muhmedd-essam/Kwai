<?php

namespace App\Models\Rooms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Rooms\Room;

class RoomModerator extends Model
{
    use HasFactory;

    use HasFactory;
    
    protected $casts = [
        'room_id' => 'integer',
        'user_id' => 'integer',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

}
