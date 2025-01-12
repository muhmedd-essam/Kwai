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

class UpdateChairsEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chairs;
    public $isUserRelated; //0 => no, 1 => yes
    public $roomID;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($chairs, $isUserRelated, $roomID)
    {
        $this->chairs = $chairs;
        $this->isUserRelated = $isUserRelated;
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
