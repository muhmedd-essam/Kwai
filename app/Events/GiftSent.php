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

class GiftSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gift;
    public $senderName;
    public $senderProfileImage;
    public $recieverName;
    public $recieverProfileImage;
    public $quantity;
    public $recieverChairIndex;
    public $roomID;
    public $allWinMultiply;

    public $vip;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($gift, $senderName, $senderProfileImage, $recieverName, $recieverProfileImage, $quantity, $recieverChairIndex, $roomID, $allWinMultiply, $vip)
    {
        $this->gift = $gift;
        $this->senderName = $senderName;
        $this->senderProfileImage = $senderProfileImage;
        $this->recieverName = $recieverName;
        $this->recieverProfileImage = $recieverProfileImage;
        $this->quantity = $quantity;
        $this->recieverChairIndex = $recieverChairIndex;
        $this->roomID = $roomID;
        $this->allWinMultiply = $allWinMultiply;
        $this->vip =$vip;
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
