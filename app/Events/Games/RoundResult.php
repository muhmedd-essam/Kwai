<?php

namespace App\Events\Games;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundResult implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $result; // 'win' أو 'lose'
    public $amount;
    public $totalCoins;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $result, $amount, $totalCoins)
    {
        $this->userId = $userId;
        $this->result = $result;
        $this->amount = $amount;
        $this->totalCoins = $totalCoins;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }
}
