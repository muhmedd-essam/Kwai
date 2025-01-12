<?php

namespace App\Http\Controllers\Admin\Rooms;

use App\Events\RoomInfoUpdate;
use App\Events\UpdateChairsEvent;
use App\Http\Controllers\Controller;
use App\Models\Rooms\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Rooms\RoomBackground;
use App\Models\Rooms\RoomChair;
use App\Traits\MobileTrait;
use Illuminate\Support\Facades\Log;

class AdminRoomBackgrounds extends Controller
{
    use MobileTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $backgrounds = RoomBackground::orderBy('id', 'DESC')->paginate(12);

        return $this->data($backgrounds);
    }
    
        public function indexRooms()
    {
        $rooms = Room::withCount('members')->get();

        return $this->data($rooms);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'cover_image' => ['required', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_free' => ['required', 'numeric', 'in:0,1'],
            'price' => ['nullable', 'numeric'],
            'room_id' => ['required','numeric'],
        ];
        // $currentRoom = Room::findOrFail('room_id');

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $path = Storage::disk('public')->putFile('images/room-backgrounds/covers', new File($request->cover_image));

        $request->request->add(['path' => $path]);
        //DB:
        try{
            $background = RoomBackground::create($request->all());

            return $this->success('S100'); //Success Insert
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $background = RoomBackground::findOrFail($id);
        
        return $this->data($background);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $background = RoomBackground::findOrFail($id);

        $rules = [
            'name' => ['string', 'min:2', 'max:50'],
            'cover_image' => ['mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_free' => ['numeric', 'in:0,1'],
            'price' => ['nullable', 'numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        if($request->hasFile('cover_image')){
            $path = Storage::disk('public')->putFile('images/room-backgrounds/covers', new File($request->cover_image));

            $request->request->add(['path' => $path]);
            Storage::disk('public')->delete($background->path);
        }
        
        //DB:
        try{
            $background->update($request->all());

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }
    
     public function updateRoom(Request $request, $id)
        {
            $room = Room::findOrFail($id);
            $user = auth()->user();
        
            $rules = [
                'rid' => ['required','numeric'],
                'name' => ['required','unique:rooms,name,' . $room->id],
                'description' => ['required', 'string'],
            ];
        
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationError(422, 'The given data was invalid.', $validator);
            }
        
            // Manual Validation for Owner
            // if ($room->owner_id != $user->id) {
            //     return $this->error('Not Allowed', 403);
            // }
        
            if (isset($request->background_id)) {
                $roomBackground = RoomBackground::find($request->background_id);
        
                if (!$roomBackground) {
                    return $this->error('خلفية الغرفة غير صالحة', 422);
                }
        
                if ($roomBackground->is_free == 0) {
                    return $this->error('هذه الخلفية غير مجانية وسيتم توفيرها قريبا!', 422);
                }
            }
        
            if ($request->hasFile('cover_image')) {
                $cover = Storage::disk('public')->putFile('images/rooms/room-covers', new File($request->cover_image));
        
                $request->merge(['cover' => $cover]);
            }
        
            if (isset($request->chairs_no)) {
                $length = $request->chairs_no == 0 ? 10 : 14;
        
                $room->chairs()->delete();
        
                for ($i = 0; $i < $length; $i++) {
                    $chair = new RoomChair;
                    $chair->index = $i;
                    $chair->is_locked = 0;
                    $chair->is_muted = 0;
                    $chair->is_muted_by_user = 0;
                    $chair->room_id = $room->id;
                    $chair->user_id = $i == 0 ? $room->owner_id : null;
                    $chair->save();
                }
            }
        
            try {
                Log::info('Updating room with data:', $request->all());
        
                $room->fill($request->only(['rid', 'name', 'description']));
        
                // Handle Password
                $room->has_password = !is_null($room->password) && $room->password !== '';
        
                Log::info('Room data before save:', $room->toArray());
        
                $room->save();
        
                Log::info('Room updated successfully:', $room->toArray());
        
                if (isset($request->chairs_no)) {
                    $roomChairs = RoomChair::where('room_id', $room->id)
                        ->with('user.defaultFrame', 'user.level')
                        ->withSum('userRecievedContributions', 'amount')
                        ->withSum('userSentContributions', 'amount')
                        ->withCount('userFollowers')
                        ->get();
                    UpdateChairsEvent::dispatch($roomChairs, 1, $room->id);
                }
        
                $updatedRoom = Room::with('background')->findOrFail($room->id);
                RoomInfoUpdate::dispatch($updatedRoom);
        
                return $this->successWithoutData('تم تعديل بيانات الغرفة بنجاح!');
            } catch (QueryException $e) {
                Log::error('Error updating room:', ['error' => $e->getMessage()]);
                return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
            }
        }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $background = RoomBackground::findOrFail($id);

        Storage::disk('public')->delete($background->path);

        return $this->success('S103');
    }
}
