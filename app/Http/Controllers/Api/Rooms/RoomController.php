<?php

namespace App\Http\Controllers\Api\Rooms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VipUser;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Rooms\Room;
use App\Models\Rooms\RoomChair;
use App\Models\Rooms\RoomMember;
use App\Models\Rooms\RoomBackground;
use App\Http\Controllers\Agora\TokenGeneratorController;
use App\Events\OwnerLeave;
use App\Events\OwnerJoin;
use App\Events\RoomChatEvent;
use App\Events\RoomInfoUpdate;
use App\Events\RoomMemberJoin;
use App\Events\RoomMembersUpdate;
use App\Events\UpdateChair;
use App\Events\UpdateChairsEvent;
use App\Models\Rooms\RoomModerator;
use App\Models\Rooms\RoomContribution;
use App\Models\HostingAgency\HostingAgencyHourPerformance;
use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyTarget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    use MobileTrait;

    /**
    * List all Rooms including user rooms
    */
    public function index() //Trending
    {
        $userRoom = null;
        if(Room::where('owner_id', auth()->id())->exists()){
            $userRoom = Room::where('owner_id', auth()->id())->first();
        }
        $currentTime = Carbon::now('UTC');
        $fiveMinutesAgo = Carbon::now('UTC')->subMinutes(5);
        $recentUpdates = Room::whereBetween('update_contributions_value_at', [$fiveMinutesAgo, $currentTime])->count();

        if ($recentUpdates > 0) {
            $rooms = Room::withCount('members')->orderByRaw("id = 1 DESC")->orderBy('update_contributions_value_at', 'DESC')->makeHidden('password');
        }else{
            $rooms = Room::withCount('members')->orderByRaw("id = 1 DESC")->orderBy('update_contributions_value_at', 'DESC')->makeHidden('password');
        }
        // $rooms = Room::withCount('members')->orderBy('contributions_value', 'DESC')->paginate(12)->makeHidden('password');

        $data = ["rooms" => $rooms, "user_room" => $userRoom];
        return $this->data($data);
    }


 public function indexNew() //New Rooms
    {
        $userRoom = null;
        if(Room::where('owner_id', auth()->id())->exists()){
            $userRoom = Room::where('owner_id', auth()->id())->first();
        }

        $rooms = Room::withCount('members')->orderBy('members_count', 'DESC')->paginate(1000)->makeHidden('password');

        $data = ["rooms" => $rooms, "user_room" => $userRoom];
        return $this->data($data);
    }

    /**
     * Create new room
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'chairs_no' => ['required', 'in:0,1'], //0: 9, 1: 12
            'type' => ['required', 'in:0,1'], //0: normal, 1: PK
            'name' => ['required'],
            'description' => ['required', 'string'],
            'cover_image' => ['required', 'mimes:jpg,png,jpeg', 'max:10024'],
            'background_id' => ['nullable','numeric', 'exists:room_backgrounds,id'],
            'password' => ['nullable', 'numeric', 'digits_between:4,4'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        /* Manual Validation */

        // if($user->level < 3){
        //     return $this->error('لا يمكن إنشاء غرفة للمستخدمين أقل من مستوى 3', 403);
        // }

        //Validate created rooms count
        if(Room::where('owner_id', $user->id)->exists()){
            return $this->error('لا يمكنك إنشاء أكثر من غرفة', 403);
        }

        //Leave user of any other room(if in one)
        if($user->roomMember != null){
            if(!$this->leaveUserOfRoom($user->roomMember, $user)){
                return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
            }
        }

        if(isset($request->background_id)){
            $roomBackground = RoomBackground::where('id', $request->background_id);

            if(!$roomBackground->exists()){
                return $this->error('خلفية الغرفة غير صالحة', 422);
            }

            if($roomBackground->first()->is_free == 0){
                return $this->error('هذه الخلفية غير مجانية وسيتم توفيرها قريبا!', 422);
            }
        }
        /* Manual Validation ENDS */

        $cover = Storage::disk('public')->putFile('images/rooms/room-covers', new File($request->cover_image));

        try{
            //Store Room:
            $room = new Room;
            $room->rid = $this->generateRoomID();
            $room->chairs_no = $request->chairs_no;
            $room->type = $request->type;
            $room->name = $request->name;
            $room->description = $request->description;
            $room->cover = $cover;
            $room->background_id = $request->background_id;
            $room->owner_id = $user->id;
            $room->agora_channel_name = $this->generateAgoraChannelName();
            $room->pusher_channel_name = $this->generateTempPusherChannelName();
            if(isset($request->password)){
                $room->password = $request->password;
                $room->has_password = 1;
            }
            $room->save();

            $room->pusher_channel_name = 'rooms.'.$room->id;
            $room->save();

            //Handle Chairs:
            if($room->chairs_no == 0){
                $length = 10;
            }elseif($room->chairs_no == 1){
                $length = 15;
            }
            for($i = 0; $i < $length; $i++){
                $chair = new RoomChair;
                $chair->index = $i;
                $chair->is_locked = 0;
                $chair->is_muted = 0;
                $chair->is_muted_by_user = 0;
                $chair->room_id = $room->id;
                $chair->user_id = null;
                $chair->save();
            }

            //Hande member:
            $member = new RoomMember;
            $member->room_id = $room->id;
            $member->user_id = $user->id;
            $member->save();

            //Handle Chair:
            $ownerChair = RoomChair::where('room_id', $room->id)->where('index', 0)->first();
            $ownerChair->user_id = $user->id;
            $ownerChair->save();

            //Agora Token
            $agoraToken = new TokenGeneratorController(101, $room->agora_channel_name);
            $agoraToken = $agoraToken->generateToken();

            // $room = Room::with('background' ,'chairs.user.defaultFrame', 'chairs.user.defaultEntry', 'owner', 'members.user')->withCount('members', 'contributions')->findOrFail($room->id);

            $room = Room::with('owner', 'background')->withCount('members')->withSum('contributions', 'amount')->findOrFail($room->id);

            $chairs = RoomChair::where('room_id', $room->id)->with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->get();

            $members = RoomMember::where('room_id', $room->id)->with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->get();

            $data = ["room" => $room, 'chairs' => $chairs, 'members' => $members, "agora_token" => $agoraToken, "user_id" => (int)$user->id];
            return $this->success($data ,'تم إنشاء الغرفة بنجاح!');
        }catch(QueryException $e){
            return $e;
            return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
        }

    }

    public function update(Request $request, $id) //Room ID
    {
        $room = Room::findOrFail($id);
        $user = auth()->user();

        $rules = [
            'name' => ['unique:rooms,name,'.$room->id],
            'description' => ['string'],
            'cover_image' => ['mimes:jpg,png,jpeg', 'max:1024'],
            // !!MUST BE SENT ALWAYS!!
            'background_id' => ['nullable' ,'numeric', 'exists:room_backgrounds,id'],
            'chairs_no' => ['numeric', 'in:0,1'],
            'password' => ['nullable', 'numeric', 'digits_between:4,4'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        /* Manual Validation */
        if($room->owner_id != $user->id){
            return $this->error('Not Allowed', 403);
        }

        if(isset($request->background_id)){
            $roomBackground = RoomBackground::where('id', $request->background_id);

            if(!$roomBackground->exists()){
                return $this->error('خلفية الغرفة غير صالحة', 422);
            }

            if($roomBackground->first()->is_free == 0){
                return $this->error('هذه الخلفية غير مجانية وسيتم توفيرها قريبا!', 422);
            }
        }

        if($request->hasFile('cover_image')){
            $cover = Storage::disk('public')->putFile('images/rooms/room-covers', new File($request->cover_image));

            $request->request->add(['cover' => $cover]);
        }
        /* Manual Validation ENDS */

        if(isset($request->chairs_no)){
            if($request->chairs_no == 0){
                $length = 10;
            }elseif($request->chairs_no == 1){
                $length = 15;
            }

            $room->chairs()->delete();

            //Insert New Chairs
            for($i = 0; $i < $length; $i++){
                $chair = new RoomChair;
                $chair->index = $i;
                $chair->is_locked = 0;
                $chair->is_muted = 0;
                $chair->is_muted_by_user = 0;
                $chair->room_id = $room->id;
                if($i == 0){
                    $chair->user_id = $room->owner_id;
                }else{
                    $chair->user_id = null;
                }
                $chair->save();
            }
        }

        try{
            $room->update($request->only('name', 'description', 'cover','background_id', 'password', 'chairs_no'));

            //Handle Password
            if($room->password != null){
                $room->has_password = 1;
            }else{
                $room->has_password = 0;
            }

            $room->save();

            if(isset($request->chairs_no)){
                $roomChairs = RoomChair::where('room_id', $room->id)->with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->get();
                UpdateChairsEvent::dispatch($roomChairs, 1, $room->id);
            }

            $updatedRoom = Room::with('background')->findOrFail($room->id);
            RoomInfoUpdate::dispatch($updatedRoom);

            return $this->successWithoutData('تم تعديل بيانات الغرفة بنجاح!');
        }catch(QueryException $e){
            return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
        }
    }

    /**
     * Join a user into room
    */
    public function join(Request $request, $id)
    {
        $user = auth()->user();
        $room = Room::with('background')->findOrFail($id);

        //Check room owner is in the room
        // if($room->owner_in_room == 0){
        //     if($room->owner_id != $user->id){
        //         return $this->error('صاحب الغرفة غير متواجد', 403);
        //     }
        // }

        //Check password
        if($room->password != null && $user->id != $room->owner_id){
            if($request->password != $room->password){
                return $this->error('كلمة السر خاطئة', 403);
            }
        }

        //Check block list
        foreach($room->blocks as $block){
            if($block->user_id == auth()->id()){
                return $this->error('عفوا، تم حظرك من دخول هذه الغرفة', 403);
            }
        }

        //Leave USER of any other room(if In one)
        if($user->roomMember != null){
            if(!$this->leaveUserOfRoom($user->roomMember, $user)){
                return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
            }
        }

        //Check if the user is the room owner
        $agoraRole = 1;
        if($room->owner_id == $user->id){
            $agoraRole = 101;
        }

        try{
            $member = new RoomMember;
            $member->room_id = $room->id;
            $member->user_id = $user->id;
            //Check if user is a moderator
            foreach(RoomModerator::where('room_id', $room->id)->get() as $moderator){
                if($moderator->user_id == $user->id){
                    $member->is_moderator = 1;
                }
            }
            $member->save();

            if(count(RoomMember::where('user_id', $user->id)->get()) > 1)
            {
                $badMembered = RoomMember::where('user_id', $user->id)->get();
                foreach($badMembered as $member){
                    $member->delete();
                }

                return $this->error('برجاء التمهل قليلا', 400);
            }

            //Agora Token
            $agoraToken = new TokenGeneratorController($agoraRole, $room->agora_channel_name);
            $agoraToken = $agoraToken->generateToken();

            // $room = Room::with('chairs.user.defaultFrame', 'chairs.user.defaultEntry', 'owner', 'members.user.defaultFrame', 'members.user.defaultEntry', 'background')->withCount('members')->findOrFail($room->id);

            // $room = Room::with('owner', 'background', 'chairs.user.defaultFrame', 'chairs.user.level', 'members.user.defaultFrame', 'members.user.level')->withCount('members', 'chairs.userFollowers', 'members.userFollowers')->withSum('chairs.userRecievedContributions', 'amount')->withSum('chairs.userSentContributions', 'amount')->withSum('members.userRecievedContributions', 'amount')->withSum('members.userSentContributions', 'amount')->findOrFail($room->id);

            // $room = Room::with('owner', 'background', 'chairs.user.defaultFrame', 'chairs.user.level', 'members.user.defaultFrame', 'members.user.level')->findOrFail($room->id);

            $room = Room::with('owner', 'background')->withCount('members')->withSum('contributions', 'amount')->findOrFail($room->id);

            $chairs = RoomChair::where('room_id', $room->id)->with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->get();

            $members = RoomMember::where('room_id', $room->id)->with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->get();

            $data = ["room" => $room, "chairs" => $chairs, 'members' => $members, "agora_token" => $agoraToken, "user_id" => (int)$user->id];

            //Handel owner_in_room FLAG
            $roomOwner = User::findOrFail($room->owner_id);
            if($user->id == $room->owner_id){
                $room->owner_in_room = 1;
                $room->save();
                OwnerJoin::dispatch($roomOwner, $room->id);
            }

            //Handle Events
            $newMember = RoomMember::where('user_id', $user->id)->with('user.defaultFrame', 'user.level', 'user.defaultEntry')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->first();

            RoomMembersUpdate::dispatch($newMember, 1, count($members), $room->id);
            $vip = VipUser::find( $user->vip);


            $message = $user->name . " دخل الغرفة";
            RoomChatEvent::dispatch($message, 4, $room->id, $user->id, $user->name, $user->profile_picture, $vip,  $user->level_id );

            RoomMemberJoin::dispatch($room->id, count($members),  $user->name, $vip);
            return $this->success($data ,'تم دخول الغرفة');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما! برجاء المحاولة لاحقا', 500);
        }
    }

    /**
     * Leave user of a Room
    */
    public function leave()
    {
        $user = auth()->user();

        if($user->roomMember == null){
            return $this->error('أنت غير متواجد في أي غرفه حاليا', 403);
        }

        try{
            if(!$this->leaveUserOfRoom($user->roomMember, $user)){
                return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
            }

            return $this->successWithoutData('تم مغادرة الغرفة بنجاح');
        }catch(QueryException $e){
            return $this->error('حدث خطأ ما، برجاء إعادة المحاولة', 500);
        }
    }

    public function deleteRoom($id) //Room ID
    {
        // $room = Room::findOrFail($id);

        // //Validate room owner is the request user
        // if($room->owner_id != auth()->id()){
        //     return $this->error('هذا الإجراء خاص بصاحب الغرفة فقط', 403);
        // }
        // $room->delete();

        // return $this->successWithoutData('تم حذف الغرفة!');

        $rooms = Room::all();
        foreach($rooms as $room){
            $room->delete();
        }

        return $this->successWithoutData('متنساش البورفيشناليزم');
    }

    protected function generateRoomID()
    {
        $roomID = rand(10000000, 99999999);

        if($this->RoomIDExists($roomID)){
            $this->generateRoomID();
        }

        return $roomID;
    }

    protected function RoomIDExists($id)
    {
        if(Room::where('rid', $id)->exists()){
            return true;
        }

        return false;
    }

    protected function generateAgoraChannelName()
    {
        $length = 10;
        $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $totalChars = strlen($chars);
        $totalRepeat = ceil($length/$totalChars);
        $repeatString = str_repeat($chars, $totalRepeat);
        $shuffleString = str_shuffle($repeatString);
        $channelName = substr($shuffleString,1,$length);

        if($this->agoraChannelNameExists($channelName)){
            $this->generateAgoraChannelName();
        }

        return $channelName;
    }

    protected function agoraChannelNameExists($channelName)
    {
        if(Room::where('agora_channel_name', $channelName)->exists()){
            return true;
        }

        return false;
    }

    protected function generateTempPusherChannelName()
    {
        $length = 10;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $totalChars = strlen($chars);
        $totalRepeat = ceil($length/$totalChars);
        $repeatString = str_repeat($chars, $totalRepeat);
        $shuffleString = str_shuffle($repeatString);
        $channelName = substr($shuffleString,1,$length);

        return $channelName;
    }

    public function getAllRoomsContributions()
    {
        $now = Carbon::now();
        $requestUser = auth()->user();
        $lastSaturday = $now->startOfWeek(Carbon::SATURDAY)->format('Y-m-d');
        $lastFriday = $now->endOfWeek(Carbon::FRIDAY)->format('Y-m-d');

        //Daily Senders
        $dailyTop10Senders =  DB::table('room_contributions')->whereDate('created_at', Carbon::today())
        ->select(DB::raw('sender_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('sender_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalDailyTop10Senders = array();

        if($dailyTop10Senders){
            foreach($dailyTop10Senders as $sender){
                $senderAmount = RoomContribution::where('sender_id', $sender->sender_id)->whereDate('created_at', Carbon::today())->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($sender->sender_id);

                $totalAmount = 0;
                foreach($senderAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalDailyTop10Senders[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        //Weekly Senders
        $weeklyTop10Senders =  DB::table('room_contributions')->whereBetween('created_at', [$lastSaturday, $lastFriday])
        ->select(DB::raw('sender_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('sender_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalWeeklyTop10Senders = array();

        if($weeklyTop10Senders){
            foreach($weeklyTop10Senders as $sender){
                $senderAmount = RoomContribution::where('sender_id', $sender->sender_id)->whereBetween('created_at', [$lastSaturday, $lastFriday])->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($sender->sender_id);

                $totalAmount = 0;
                foreach($senderAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalWeeklyTop10Senders[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        //Daily recievers
        $dailyTop10Recievers =  DB::table('room_contributions')->whereDate('created_at', Carbon::today())
        ->select(DB::raw('receiver_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('receiver_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalDailyTop10Recievers = array();

        if($dailyTop10Recievers){
            foreach($dailyTop10Recievers as $reciever){
                $recieverAmount = RoomContribution::where('receiver_id', $reciever->receiver_id)->whereDate('created_at', Carbon::today())->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($reciever->receiver_id);

                $totalAmount = 0;
                foreach($recieverAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalDailyTop10Recievers[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        //Weekly recievers
        $weeklyTop10Recievers =  DB::table('room_contributions')->whereBetween('created_at', [$lastSaturday, $lastFriday])
        ->select(DB::raw('receiver_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('receiver_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalWeeklyTop10Recievers = array();

        if($weeklyTop10Recievers){
            foreach($weeklyTop10Recievers as $reciever){
                $recieverAmount = RoomContribution::where('receiver_id', $reciever->receiver_id)->whereBetween('created_at', [$lastSaturday, $lastFriday])->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($reciever->receiver_id);

                $totalAmount = 0;
                foreach($recieverAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalWeeklyTop10Recievers[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        /* Monthly */
        $today = \Carbon\Carbon::now(); //Current Date and Time

        $lastDayofMonth =    \Carbon\Carbon::parse($today)->endOfMonth()->toDateString();

        $firstDayofMonth =    \Carbon\Carbon::parse($today)->firstOfMonth()->toDateString();

        //Monthly recievers
        $monthlyTop10Recievers =  DB::table('room_contributions')->whereDate('created_at', [$firstDayofMonth, $lastDayofMonth])
        ->select(DB::raw('receiver_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('receiver_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalMonthlyTop10Recievers = array();

        if($monthlyTop10Recievers){
            foreach($monthlyTop10Recievers as $reciever){
                $recieverAmount = RoomContribution::where('receiver_id', $reciever->receiver_id)->whereBetween('created_at', [$firstDayofMonth, $lastDayofMonth])->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($reciever->receiver_id);

                $totalAmount = 0;
                foreach($recieverAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalMonthlyTop10Recievers[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        //Monthly Senders
        $monthlyTop10Senders =  DB::table('room_contributions')->whereBetween('created_at', [$firstDayofMonth, $lastDayofMonth])
        ->select(DB::raw('sender_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('sender_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalMonthlyTop10Senders = array();

        if($monthlyTop10Senders){
            foreach($monthlyTop10Senders as $sender){
                $senderAmount = RoomContribution::where('sender_id', $sender->sender_id)->whereBetween('created_at', [$firstDayofMonth, $lastDayofMonth])->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($sender->sender_id);

                $totalAmount = 0;
                foreach($senderAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalMonthlyTop10Senders[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        /* Request User */
        //Request user: daily(Sending)
        $finalRequestUserDailySending = 0;
        $allRequestUserDailySending = RoomContribution::where('sender_id', $requestUser->id)->whereDate('created_at', Carbon::today())->get('amount');
        foreach($allRequestUserDailySending as $val){
            $finalRequestUserDailySending+= $val->amount;
        }

        //Request user: weekly(Sending)
        $finalRequestUserWeeklySending = 0;
        $allRequestUserWeeklySending = RoomContribution::where('sender_id', $requestUser->id)->whereBetween('created_at', [$lastSaturday, $lastFriday])->get('amount');
        foreach($allRequestUserWeeklySending as $val){
            $finalRequestUserWeeklySending+= $val->amount;
        }

        //Request user: daily(Recieved)
        $finalRequestUserDailyRecieved = 0;
        $allRequestUserDailyRecieved = RoomContribution::where('receiver_id', $requestUser->id)->whereDate('created_at', Carbon::today())->get('amount');
        foreach($allRequestUserDailyRecieved as $val){
            $finalRequestUserDailyRecieved+= $val->amount;
        }

        //Request user: weekly(Recieved)
        $finalRequestUserWeeklyRecieved = 0;
        $allRequestUserWeeklyRecieved = RoomContribution::where('receiver_id', $requestUser->id)->whereBetween('created_at', [$lastSaturday, $lastFriday])->get('amount');
        foreach($allRequestUserWeeklyRecieved as $val){
            $finalRequestUserWeeklyRecieved+= $val->amount;
        }

        $data = ['dailyTop10Senders' => $finalDailyTop10Senders, 'weeklyTop10Senders' => $finalWeeklyTop10Senders, 'dailyTop10Recievers' => $finalDailyTop10Recievers, 'weeklyTop10Recievers' => $finalWeeklyTop10Recievers, 'finalRequestUserDailySending' => $finalRequestUserDailySending, 'finalRequestUserWeeklySending' => $finalRequestUserWeeklySending, 'finalRequestUserDailyRecieved' => $finalRequestUserDailyRecieved, 'finalRequestUserWeeklyRecieved' => $finalRequestUserWeeklyRecieved, 'finalMonthlyTop10Senders' => $finalMonthlyTop10Senders, 'finalMonthlyTop10Recievers' => $finalMonthlyTop10Recievers];

        return $this->data($data);
    }

    protected function leaveUserOfRoom($member, $user)
    {
        try{
            $oldRoom = $member->room;

            //Leave Him of a chair(if on one)
            if(RoomChair::where('user_id', $user->id)->exists())
            {
                $userChair = RoomChair::where('user_id', $user->id)->first();
                $userChair->is_muted_by_user = 0;

                $userChair->user_id = null;

                $userChair->carizma_counter = 0;
                $userChair->carizma_opened_at = null;

                $userChair->save();

                $updatedChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($userChair->id);
                UpdateChair::dispatch($updatedChair, 1, $oldRoom->id);

            /* Hosting Agency */
            if($user->is_hosting_agent == 1){
                $agencyMembership = $user->hostingAgencyMember()->with('agency')->first();
                $start = Carbon::parse($userChair->user_up_at);
                $end = Carbon::parse(now());
                $diff = $end->diffInMinutes ($start);
                $diff = round($diff/60, 2);

                if($diff > 0.25){

                    if($diff > 2){
                        $diff = 2;
                    }

                    $totalDuration = HostingAgencyHourPerformance::where('agency_member_id', $agencyMembership->id)->whereDate('created_at', '=', now()->format('Y-m-d'))->sum('duration');
                    if($totalDuration < 2){

                        $hoursLeft = 2 - $totalDuration;

                        if($diff > $hoursLeft){
                            $diff = $hoursLeft;
                        }

                        HostingAgencyHourPerformance::insert(['agency_member_id' => $agencyMembership->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'duration' => $diff, 'created_at' => now(), 'updated_at' => now()]);

                        $this->levelTargetUp($agencyMembership);
                        }
                    }
                }
            }

            //Handle 'owner_in_room' FLAG
            if($user->id == $oldRoom->owner_id){
                $oldRoom->owner_in_room = 0;
                $oldRoom->save();
                //Handle Events
                OwnerLeave::dispatch(User::find($oldRoom->owner_id), $oldRoom->id);
            }

            //Handle Events
            $newRoomMembers = RoomMember::where('room_id', $oldRoom->id)->get();

            $newMember = RoomMember::where('user_id', $user->id)->with('user.defaultFrame', 'user.defaultEntry')->first();

            RoomMembersUpdate::dispatch($newMember, 0, count($newRoomMembers) - 1, $oldRoom->id);
            $vip = VipUser::find( $user->vip);

            $message = $user->name . " غادر الغرفة";
            RoomChatEvent::dispatch($message, 4, $oldRoom->id, $user->id, $user->name, $user->profile_picture, $vip, $user->level_id);

            $newMember->delete();

            return true;
        }catch(QueryException $e){
            return false;
        }
    }

    protected function levelTargetUp($membership)
    {
        $currentTarget = $membership->target()->first();

        $nextTarget = HostingAgencyTarget::where('target_no', $currentTarget->target_no + 1)->first();

        if($nextTarget && HostingAgencyDiamondPerformance::where('agency_member_id', $membership->id)->sum('amount') >= $nextTarget->diamonds_required && HostingAgencyHourPerformance::where('agency_member_id', $membership->id)->sum('duration') >= $nextTarget->hours_required){

            $membership->current_target_id = $nextTarget->id;
            $membership->save();

            $this->levelTargetUp($membership);
        }else{
            return;
        }
    }

}
