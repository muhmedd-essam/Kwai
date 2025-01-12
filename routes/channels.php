<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('rooms.{roomID}', function ($roomID) {
    
    return true;

    $room = Room::with('members.user')->findOrFail($roomID);
    
    foreach($room->members as $member){
        if(auth()->id() == $member->user->id){
            return true;
        }
    }

    return false;
});

Broadcast::channel('video-rooms.{roomID}', function ($roomID) {
    
    return true;

});

Broadcast::channel('global-rooms', function () {
    
    return true;

});

Broadcast::channel('global-video-rooms', function () {
    
    return true;

});

Broadcast::channel('conversation.{convID}', function ($convID) {
    
    return true;
    
});

Broadcast::channel('inbox.{userID}', function ($userID) {
    
    return true;
    
});