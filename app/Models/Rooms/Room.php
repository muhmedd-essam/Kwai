<?php

namespace App\Models\Rooms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use App\Models\Rooms\RoomBlock;
use App\Models\Rooms\RoomChair;
use App\Models\Rooms\RoomMember;
use App\Models\Rooms\RoomContribution;
use App\Models\Rooms\RoomModerator;
use App\Models\Rooms\RoomBackground;
use App\Models\User;
use App\Models\Banner;

class Room extends Model
{
    use BroadcastsEvents, HasFactory;

    protected $fillable = [
        'rid',
        'name',
        'description',
        'cover',
        'background_id',
        'password',
        'chairs_no',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'rid' => 'integer',
        'chairs_no' => 'integer',
        'type' => 'integer',
        'owner_id' => 'integer',
        'owner_in_room' => 'integer',
        'has_password' => 'integer',
    ];

    public function getCoverAttribute($value){
        return asset('storage/' . $value);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(RoomMember::class);
    }

    public function chairs()
    {
        return $this->hasMany(RoomChair::class);
    }


    public function blocks()
    {
        return $this->hasMany(RoomBlock::class);
    }

    public function contributions()
    {
        return $this->hasMany(RoomContribution::class);
    }

    public function moderators()
    {
        return $this->hasMany(RoomModerator::class);
    }

    public function background()
    {
        return $this->belongsTo(RoomBackground::class, 'background_id');
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn($event)
    {
        return [new PrivateChannel('rooms.'.$this->id)];
    }

    public function banners()
    {
        return $this->morphMany(Banner::class, 'related_to');
    }

    public function roomBackground()
    {
        return $this->hasMany(RoomBackground::class, 'room_id', 'id');
    }

}
