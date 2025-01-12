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

class VideoGiftSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gift;
    public $sender;
    public $quantity;
    public $roomID;
    public $winMultiply;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($gift, $sender, $quantity,$roomID, $winMultiply)
    {
        $this->gift = $gift;
        $this->sender = $sender;
        $this->quantity = $quantity;
        $this->roomID = $roomID;
        $this->winMultiply = $winMultiply;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('video-rooms.' . $this->roomID);
    }
}
