<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Models\Rooms\Room;

class RoomChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $type; // 0 => normal, 1 => room description , 2 => gift, 3 => lucky bags, 4 => join or leave, => 5 => block
    public $roomID;
    public $userID;
    public $userName;
    public $profile_picture;
    public $level_id;
    public $vip;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message, $type, $roomID, $userID, $userName, $profile_picture, $level_id, $vip)
    {

        $this->message = $message;
        $this->type = $type;
        $this->roomID = $roomID;
        $this->userID = $userID;
        $this->userName = $userName;
        $this->profile_picture = $profile_picture;
        // dd($profile_picture);
        $this->level_id = $level_id;
        $this->vip = $vip;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('rooms.' . $this->roomID);
    }
}
