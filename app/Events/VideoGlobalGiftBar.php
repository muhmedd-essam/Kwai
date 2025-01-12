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

class VideoGlobalGiftBar implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gift;
    public $quantity;
    public $sender;
    public $reciever;
    public $roomID;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($gift, $quantity, $sender, $reciever, $roomID)
    {
        $this->gift = $gift;
        $this->quantity = $quantity;
        $this->sender = $sender;
        $this->reciever = $reciever;
        $this->roomID = $roomID;        
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('global-video-rooms');
    }
}
