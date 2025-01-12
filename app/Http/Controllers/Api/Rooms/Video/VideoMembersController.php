<?php

namespace App\Http\Controllers\Api\Rooms\Video;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\Rooms\Video\VideoRoom;
use App\Models\Rooms\Video\VideoRoomMember;
use App\Events\VideoRooms\MemberJoined;

class VideoMembersController extends Controller
{
    use MobileTrait;

    public function join($id)
    {
        $videoRoom = VideoRoom::findOrFail($id);

        $requestUser = auth()->user();
        
        if($requestUser->id == $videoRoom->user_id){
            return $this->error('لا يمكنك دخول غرفتك وانت بالفعل صاحبها', 403);    
        }

        try{
            $this->leave();

            VideoRoomMember::insert(['video_room_id' => $id, 'user_id' => $requestUser->id, 'created_at' => now(), 'updated_at' => now()]);

            MemberJoined::dispatch($videoRoom->id, $requestUser->with('defaultEntry')->first());

            return $this->successWithoutData('member joined');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function leave()
    {
        $roomMember = VideoRoomMember::where('user_id', auth()->id());

        if(!$roomMember->exists()){
            return $this->error('you are not in any room yet!', 403);  
        }

        $roomMember = $roomMember->first();

        try{
            $roomMember->delete();

            return $this->successWithoutData('member left :( ');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ban(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function kick($id)
    {
        //
    }

}
