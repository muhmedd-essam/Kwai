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

class RoomMembersUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $member;
    public $status; // 1 => join, 0 => leave, 2 => block
    public $membersCount;
    public $roomID;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($member, $status, $membersCount, $roomID)
    {
        $this->member = $member;
        $this->status = $status;
        $this->membersCount = $membersCount;
        $this->roomID = $roomID;
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
