<?php

namespace App\Models\Rooms\Video;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\Rooms\Video\VideoRoomMember;
use App\Models\User;

class VideoRoom extends Model
{
    use HasFactory;

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members()
    {
        return $this->hasMany(VideoRoomMember::class, 'video_room_id');
    }

    public function next(){
        $next = $this->with('owner')->withCount('members')->where('id', '>', $this->id)->orderBy('id','asc')->first();
    
        if($next == null){
            $previous = $this->with('owner')->withCount('members')->where('id', '<', $this->id)->orderBy('id','desc')->first();

            if($previous == null){
                return null;
            }
            return $previous;
        }
        
        return $next;
    }

    public function previous(){
        $previous = $this->with('owner')->withCount('members')->where('id', '<', $this->id)->orderBy('id','desc')->first();

        if($previous == null){
            $next = $this->with('owner')->withCount('members')->where('id', '>', $this->id)->orderBy('id','asc')->first();
            
            if($next == null){
                return null;
            }
            return $next;
        }

        return $previous;
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return \Illuminate\Broadcasting\Channel|array
     */
    // public function broadcastOn($event)
    // {
    //     return [new PrivateChannel('rooms.'.$this->id)];
    // }
}
