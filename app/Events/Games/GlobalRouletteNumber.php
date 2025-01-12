<?php

namespace App\Events\Games;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GlobalRouletteNumber
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $randomNumber;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($randomNumber)
    {
        $this->randomNumber = $randomNumber;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('global-roulette');
    }

    public function broadcastAs()
    {
        return 'random-number-generated';
    }
}
