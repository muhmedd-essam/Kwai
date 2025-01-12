<?php

namespace App\Models\Rooms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Rooms\Room;
use App\Models\Rooms\RoomContribution;
use App\Models\Following;
use Carbon\Carbon;

class RoomChair extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_muted_by_user',
    ];

    protected $casts = [
        'index' => 'integer',
        'status' => 'integer',
        'room_id' => 'integer',
        'user_id' => 'integer',
        'is_muted_by_user' => 'integer',
    ];

    // public function setUserIdAttribute($value)
    // {
    //     if($this->index){
    //         $this->attributes['user_id'] = $value;
    //     }elseif($this->index == 0 && $this->user_id == null){
    //         $this->attributes['user_id'] = $value;
    //     }
    // }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userRecievedContributions()
    {
        $carbonMinus7Days = Carbon::now()->subDays(7);
        return $this->hasMany(RoomContribution::class, 'receiver_id', 'user_id')->whereDate('created_at', '>=', $carbonMinus7Days);
    }

    public function userSentContributions()
    {
        $carbonMinus7Days = Carbon::now()->subDays(7);
        return $this->hasMany(RoomContribution::class, 'sender_id', 'user_id')->whereDate('created_at', '>=', $carbonMinus7Days);
    }

    public function userFollowers()
    {
        return $this->hasMany(Following::class, 'following_id', 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

}
