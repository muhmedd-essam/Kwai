<?php

namespace App\Events\Games;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundStatusUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $status; // 'started' أو 'ended'
    public $timeRemaining;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($status, $timeRemaining)
    {
        $this->status = $status;
        $this->timeRemaining = $timeRemaining;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('game-round');
    }
}
