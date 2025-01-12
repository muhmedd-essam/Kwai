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

class RoomMemberJoin implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomID;
    public $membersCount;
    public $userName;
    public $vip;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($roomID, $membersCount, $userName,$vip)
    {
        $this->roomID = $roomID;
        $this->membersCount = $membersCount;
        $this->userName = $userName;
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
