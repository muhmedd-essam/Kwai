<?php

namespace App\Models\Rooms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Rooms\Room;
use App\Models\Gift;

class RoomContribution extends Model
{
    protected $casts = [
        'room_id' => 'integer',
        'receiver_id' => 'integer',
        'sender_id' => 'integer',
        'gift_id' => 'integer',
        'amount' => 'float',
    ];
    
    use HasFactory;

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class);
    }
}
