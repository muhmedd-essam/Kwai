<?php

namespace App\Http\Controllers\Api\Rooms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Rooms\RoomBackground;
use App\Models\Rooms\RoomMember;

class RoomBackgrounds extends Controller
{
    use WebTrait;



    public function store(Request $request)
    {
        $rules = [
            // 'name' => ['required', 'string', 'min:2', 'max:50'],
            'cover_image' => ['required', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];

        $user = auth()->user();

        // $currentRoom = RoomMember::where('user_id', $user->id)->with('room')->first();
        // $currentRoom->room->background_id;

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $path = Storage::disk('public')->putFile('images/room-backgrounds/covers', new File($request->cover_image));

        $request->request->add(['path' => $path]);
        // $request->request->add(['room_id' => $currentRoom->room->id]);
        
        // return $this->data($currentRoom->room->id);


        //DB:
        try{
            $background = RoomBackground::create($request->all());
            
            // RoomBackground::insert(['agency_member_id' => $agencyMembership->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'duration' => $diff, 'created_at' => now(), 'updated_at' => now()]);


            // $currentRoom->room->background_id = $background->id;
            // $currentRoom->room->save();
            return $this->success('S100'); //Success Insert
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function index(){

        $user= auth()->user();
        $currentRoom = RoomMember::where('user_id', $user->id)->with('room')->first();
        $idRoom = $currentRoom->room->id;
        $data= RoomBackground::where('room_id', $idRoom)->get();

        return $this->data($data);

    }
}

