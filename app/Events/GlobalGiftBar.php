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

class GlobalGiftBar implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $giftCover;
    public $giftPrice;
    public $quantity;

    public $senderName;
    public $senderProfileImage;
    public $recieverName;
    public $recieverProfileImage;

    public $roomID;
    public $pucherChannelName;
    public $agoraChannelName;
    public $type;
    public $allWinMultiply;

    public $vip;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($giftCover, $giftPrice, $quantity, $senderName, $senderProfileImage, $recieverName, $recieverProfileImage, $roomID, $pucherChannelName, $agoraChannelName, $type, $allWinMultiply, $vip)
    {
        $this->giftCover = $giftCover;
        $this->giftPrice = $giftPrice;
        $this->quantity = $quantity;

        $this->senderName = $senderName;
        $this->senderProfileImage = $senderProfileImage;
        $this->recieverName = $recieverName;
        $this->recieverProfileImage = $recieverProfileImage;

        $this->roomID = $roomID;
        $this->pucherChannelName = $pucherChannelName;
        $this->agoraChannelName = $agoraChannelName;
        $this->type = $type;
        $this-> allWinMultiply = $allWinMultiply;

        $this-> vip = $vip;

     }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('global-rooms');
    }
}
