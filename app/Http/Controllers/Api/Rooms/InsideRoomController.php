<?php

namespace App\Http\Controllers\Api\Rooms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VipUser;
use Illuminate\Support\Facades\Cache;

use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Models\Rooms\Room;
use App\Models\Rooms\RoomChair;
use App\Models\Rooms\RoomMember;
use App\Models\Rooms\RoomModerator;
use App\Models\Rooms\RoomBlock;
use App\Models\Rooms\RoomContribution;
use App\Models\Gift;
use App\Models\Rooms\Emoji;
use App\Models\Level;
use App\Models\Rooms\RoomBackground;
use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyHourPerformance;
use App\Models\HostingAgency\HostingAgencyTarget;
use App\Models\GlobalBox;
use App\Events\UpdateChairsEvent;
use App\Events\RoomChatEvent;
use App\Events\UpdateChair;
use App\Events\GiftSent;
use App\Events\GiftSentToAll;
use App\Events\EmojiSent;
use App\Events\GlobalGiftBar;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\HostingAgency\HostingAgencyMemberBd;
use App\Models\HostingAgency\HostingAgencyTargetBd;

class InsideRoomController extends Controller
{
    use MobileTrait;

    public function upToChair($chairID)
    {
        $chair = RoomChair::findOrFail($chairID);
        $user = auth()->user();

        //Validate user is not the owner of the room
        // if($chair->room()->first()->owner_id == $user->id){
        //     return $this->error('لا يمكن لصاحب الغرفة تغيير الكرسي', 403);
        // }

        //Validate Chair is opened
        if($chair->is_locked == 1){
            return $this->error('الكرسي مغلق ', 403);
        }

        //Validate Chair is empty
        if($chair->user_id != null){
            return $this->error('الكرسي غير فارغ', 403);
        }

        //Validate User is in the room
        if($user->roomMember == null){
            return $this->error('أنت غير متواجد في الغرفة', 403);
        }

        if($chair->room_id != $user->roomMember->room_id){
            return $this->error('أنت غير متواجد في الغرفة', 403);
        }

        //Handle old Chair
        $oldUserChair = RoomChair::where('user_id', $user->id);
        // return response()->json(['message'=> $oldUserChair, 200]);

        if($oldUserChair->exists()){
            $oldUserChair = $oldUserChair->first();

            $oldUserChair->user_id = null;

            $oldUserChair->is_muted_by_user = 0;
            $oldUserChair->carizma_counter = 0;
            $oldUserChair->save();

            $updatedOldUserChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($oldUserChair->id);

            UpdateChair::dispatch($updatedOldUserChair, 1, $oldUserChair->room_id);

            /* Hosting Agency */
            if($user->is_hosting_agent == 1){
                $agencyMembership = $user->hostingAgencyMember()->with('agency')->first();
                $start = Carbon::parse($oldUserChair->user_up_at);
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

        try{
            $chair->user_id = $user->id;
            $chair->user_up_at = now();

            if($chair->is_carizma_counter == 1){
                $chair->carizma_opened_at = now();
            }
            $chair->carizma_opened_at = null;

            $chair->save();
             // update day salary

             $memberAgency = HostingAgencyMember::where('user_id', $user->id)->first();

            if ($memberAgency !== null) {
                $today = Carbon::now('Africa/Cairo')->startOfDay();

                if ($memberAgency->update_day_salary_at != $today) {
                    $memberAgency->day_salary -= 1;
                    $memberAgency->update_day_salary_at = $today;
                    $memberAgency->save();
                }
            }

            //Handle Events
            $updatedChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($chair->id);

            UpdateChair::dispatch($updatedChair, 1, $chair->room_id);




            return $this->success($chair ,'تم الصعود على الكرسي');
        }catch(QueryException $e){
            return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function leaveChair()
    {
        $user = auth()->user();
        $member = $user->roomMember;
        $room = Room::findOrFail($member->room_id);

        if($member == null){
            return $this->error('أنت لست عضو في أي الغرفة', 403);
        }

        // if($room->owner_id == $user->id){
        //     return $this->error('لا يمكن لصاحب الغرفة مغادرة الكرسي', 403);
        // }

        $userChair = RoomChair::where('user_id', $user->id);
        if(!$userChair->exists()){
            return $this->error('أنت غير متواجد على أي كرسي', 403);
        }
        $userChair = $userChair->first();

        try{
            $userChair->user_id = null;

            $userChair->is_muted_by_user = 0;
            $userChair->carizma_counter = 0;
            $userChair->carizma_opened_at = null;
            $userChair->save();

            //Handle Events
            $newChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($userChair->id);
            UpdateChair::dispatch($newChair, 1, $room->id);

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
                }}


            return $this->successWithoutData('تم النزول من على الكرسي');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function sendChatMessage(Request $request, $id) //Room ID
    {
        $room = Room::findOrFail($id);

        $rules = [
            'body' => ['required', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        try{
            //Handle Events
            $vip = VipUser::find(auth()->user()->vip);
            $user = User::find(auth()->id());
            RoomChatEvent::dispatch($request->body, 0, $room->id, auth()->id(), auth()->user()->name, auth()->user()->profile_picture, $vip, auth()->user()->level_id);

            return response()->json(['message'=>'تم ارسال الرسالة', 'profie'=>$request->body, 0, $room->id, auth()->id(), auth()->user()->name, auth()->user()->profile_picture, $vip, auth()->user()->level_id, 200]);
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function muteChairByUser(Request $request, $id) //Chair ID
    {
        $chair = RoomChair::findOrFail($id);
        $user = auth()->user();

        $rules = [
            'status' => ['required', 'in:0,1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        //Validate request user is the one on the chair
        if($chair->user_id != $user->id){
            return $this->error('هذا الكرسي غير خاص بك', 403);
        }

        try{
            $chair->is_muted_by_user = (int)$request->status;
            $chair->save();

            $chairWithUser = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($chair->id);
            //Handle Events
            UpdateChair::dispatch($chairWithUser, 0, $chair->room_id);

            return $this->successWithoutData('تم تغيير حالة المايك');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function getRoomInfo($id)
    {
        $room = Room::findOrFail($id);

        $roomOwner = User::findOrFail($room->owner_id);

        $roomModerators = RoomModerator::where('room_id', $room->id)->with('user')->get();

        $data = ["room" => $room, "owner" => $roomOwner, "moderators" => $roomModerators];

        return $this->data($data);
    }

    public function getRoomMembers($id)
    {
        $roomMembers = RoomMember::where('room_id', $id)->with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->paginate(12);

        return $this->dataPaginated($roomMembers);
    }

    public function getRoomModerators($id)
    {
        $moderators = RoomModerator::with('user')->where('room_id', $id)->get();

        return $this->data($moderators);
    }

    public function getRoomBlocks($id)
    {
        $roomBlocks = RoomBlock::where('room_id', $id)->with('user')->paginate(12);

        return $this->dataPaginated($roomBlocks);
    }

    public function getContributions($id)
    {
        $room = Room::findOrFail($id);

        return $this->data($room->contributions);
    }

    public function inviteToRoom()
    {
        //
    }

    public function sendGiftToMultiPerson(Request $request, $id) //Room ID
{

    $rules = [
        'reciever_ids' => ['required', 'array'],
        'reciever_ids.*' => ['numeric'],
        'gift_id' => ['required', 'numeric'],
        'quantity' => ['required', 'numeric', 'min:1', 'max:1000'],
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return $this->validationError(422, 'The given data was invalid.', $validator);
    }

    $room = Room::findOrFail($id);

    $gift = Gift::findOrFail($request->gift_id);

    $sender = auth()->user();
    $totalCost = $gift->price * $request->quantity * count($request->reciever_ids);
    // Validate sender Balance for all recipients
    if ($sender->diamond_balance < $totalCost) {
        return $this->error('عفوا رصيدك لا يكفي لإرسال الهدايا لجميع المستلمين', 403);
    }

    foreach ($request->reciever_ids as $reciever_id) {
        $reciever = User::findOrFail($reciever_id);



        // Validate that both sender and receiver are in the same room
        if (!RoomMember::where('user_id', $sender->id)->where('room_id', $room->id)->exists() ||
            !RoomMember::where('user_id', $reciever->id)->where('room_id', $room->id)->exists()) {
            return $this->error('يجب أن يكون المرسل والمستقبل في نفس الغرفة', 403);
        }

        // Validate receiver is on a chair
        $recieverChair = RoomChair::where('user_id', $reciever->id)->where('room_id', $room->id)->first();
        if (!$recieverChair) {
            return $this->error("المستلم $reciever->name يجب أن يكون متواجدًا على أحد الكراسي", 403);
        }

        try {
            // Subtract sender balance
            $sender->diamond_balance -= $gift->price * $request->quantity;
            $sender->save();

            if ($gift->type != 2) {
                $singleDiamondPrice = 0.00065;
                $reciever->money += $singleDiamondPrice * (($gift->price * $request->quantity) * 0.5);
                $reciever->gold_balance += $gift->price * $request->quantity;
                $reciever->save();
                $allWinMultiply = null;
            } else {
                $allWinMultiply = $this->multiplyGift($gift->price * $request->quantity, $reciever);
            }

            // Update room contribution and other necessary details
             //Update room contribution
             if ($gift->type !=2 ){
                $roomCont = new RoomContribution;
                $roomCont->room_id = $room->id;
                $roomCont->receiver_id = $reciever->id;
                $roomCont->sender_id = $sender->id;
                $roomCont->gift_id = $gift->id;
                $roomCont->quantity = $request->quantity;
                $roomCont->amount = $gift->price * $request->quantity;
                $roomCont->save();

            }else{
                $roomCont = new RoomContribution;
                $roomCont->room_id = $room->id;
                $roomCont->receiver_id = $reciever->id;
                $roomCont->sender_id = $sender->id;
                $roomCont->gift_id = $gift->id;
                $roomCont->quantity = $request->quantity;
                $roomCont->amount = ($gift->price * $request->quantity) * 0.1;
                $roomCont->save();
            }


            //Update room object
            if ($gift->type != 2){
                $room->contributions_value += $gift->price * $request->quantity;
                $room->update_contributions_value_at= Carbon::now('UTC');
                $room->save();
            }else{
                $room->contributions_value += ($gift->price * $request->quantity) * 0.1;
                $room->update_contributions_value_at= Carbon::now('UTC');
                $room->save();

            }

            if($recieverChair->is_carizma_counter == 1){

                if($gift->type != 2){
                    $recieverChair->carizma_counter+= $gift->price * $request->quantity;
                }else{
                    $recieverChair->carizma_counter+= ($gift->price * $request->quantity) * 0.1;
                }

                if($recieverChair->save()){
                    $newRecieverChair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($recieverChair->id);

                    UpdateChair::dispatch($newRecieverChair, 0, $room->id);
                }
            }
            $vip = VipUser::find($sender->vip);
            GiftSent::dispatch($gift, $sender->name, $sender->profile_picture, $reciever->name, $reciever->profile_image, $request->quantity, $recieverChair->index, $room->id, $allWinMultiply, $vip);

            $message = 'أرسل ' . ' x' . $request->quantity . ' ' . $gift->name . ' إلى ' . $reciever->name;

            RoomChatEvent::dispatch($message, 2, $room->id, $sender->id, $sender->name, $sender->profile_picture, $vip, $sender->level_id);

            // Update receiver and sender experience points
            $sender->exp_points += $gift->price * $request->quantity;
            $sender->supported_send = $gift->price * $request->quantity;
            $sender->save();
            $this->levelUserUp($sender);

            $reciever->exp_points += ($gift->price * $request->quantity) * 0.5;
            $reciever->supported_recieve = $gift->price * $request->quantity;
            $reciever->save();
            $this->levelUserUp($reciever);


            /* Hosting Agency */
           if($reciever->is_hosting_agent == 1 && $gift->type != 2 ){
                    $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();
                        if (!is_null($agencyMembership)){
                            HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => $gift->price * $request->quantity, 'created_at' => now(), 'updated_at' => now()]);

                                            $this->levelTargetUp($agencyMembership);
                        }

                }elseif($reciever->is_hosting_agent == 1 && $gift->type == 2){

                    $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();
                    if (!is_null($agencyMembership)){
                         HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => ($gift->price * $request->quantity) * 0.1, 'created_at' => now(), 'updated_at' => now()]);
                    $this->levelTargetUp($agencyMembership);
                    }
                    // return $this->successWithoutData($agencyMembership);


                }

        } catch (QueryException $e) {
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }

        $reciever->gifts()->attach($gift->id, ['quantity' => $request->quantity]);
    }

    return $this->success($sender->diamond_balance, 'تم إرسال الهدايا بنجاح لجميع المستلمين');
}
    public function sendGift(Request $request, $id) //Room ID
    {
        $rules = [
            'reciever_id' => ['required', 'numeric'],
            'gift_id' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric', 'min:1', 'max:1000'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $room = Room::findOrFail($id);
        $gift = Gift::findOrFail($request->gift_id);
        $sender = auth()->user();
        $reciever = User::findOrFail($request->reciever_id);
        // return $this->data(RoomMember::where('user_id', $reciever->id)->first());

        //Validate sender Balance
        if($sender->diamond_balance < $gift->price * $request->quantity){
            return $this->error('عفوا رصيدك لا يكفي', 403);
        }

        //Validate that the sender and the reciever is in the same room of the request
        if(!RoomMember::where('user_id', $sender->id)->where('room_id', $room->id)->exists() || !RoomMember::where('user_id', $reciever->id)->where('room_id', $room->id)->exists())
        {
            return $this->error('يجب أن يكون المرسل في الغرفه وكذلك المرسل إليه', 403);
        }

        //Validate that the sender and the reciever is in the same room
        if($sender->roomMember->room_id != $reciever->roomMember->room_id){
            return $this->error('لا يمكن إرسال هدية لشخص خارج الغرفة', 403);
        }

        //Validate the reciever is on a chair
        $recieverChair = RoomChair::where('user_id', $reciever->id)->where('room_id', $room->id);
        if(!$recieverChair->exists()){
            return $this->error('يجب أن يكون مستقبل الهدية متواجد على أحد الكراسي', 403);
        }
        $recieverChair = $recieverChair->first();

        try{
            //Subtract sender balance
            $sender->diamond_balance-= $gift->price * $request->quantity;
            $sender->save();
            // $reciever->diamond_balance+= $gift->price * $request->quantity;
            // $reciever->save();

            if($gift->type != 2){
                $singleDiamondPrice = 0.00065;
                $reciever->money+= $singleDiamondPrice * (($gift->price * $request->quantity) * 0.5);

                $reciever->gold_balance +=  (($gift->price * $request->quantity) );

                $reciever->save();

                $allWinMultiply = null;
            }else{
                $allWinMultiply = $this->multiplyGift($gift->price * $request->quantity, $reciever);
                $vip = VipUser::find($sender->vip);
                if($allWinMultiply >= 500){
                GlobalGiftBar::dispatch($gift->cover, $gift->price, $request->quantity, $sender->name, $sender->profile_image, $reciever->name, $reciever->profile_image, $room->id, $room->pusher_channel_name, $room->agora_channel_name, 2, $allWinMultiply,$vip);
            }
            }

            /* Give gift to reciever */
            //check if the user already has the same gift => increment it
            // $duplicateGift = $reciever->gifts()->where('gift_id', $gift->id);
            // if($duplicateGift->exists()){
            //     $duplicateGift = $duplicateGift->withPivot('quantity')->first();

            //     $reciever->gifts()->updateExistingPivot($duplicateGift->id, ['quantity' => $duplicateGift->pivot->quantity + $request->quantity]);
            // }else{
            //     $reciever->gifts()->attach([$gift->id => ['quantity' => $request->quantity, 'created_at' => now(), 'updated_at' => now()]]);
            // }

            //Update room contribution
            if ($gift->type !=2 ){
                $roomCont = new RoomContribution;
                $roomCont->room_id = $room->id;
                $roomCont->receiver_id = $reciever->id;
                $roomCont->sender_id = $sender->id;
                $roomCont->gift_id = $gift->id;
                $roomCont->quantity = $request->quantity;
                $roomCont->amount = $gift->price * $request->quantity;
                $roomCont->save();

            }else{
                $roomCont = new RoomContribution;
                $roomCont->room_id = $room->id;
                $roomCont->receiver_id = $reciever->id;
                $roomCont->sender_id = $sender->id;
                $roomCont->gift_id = $gift->id;
                $roomCont->quantity = $request->quantity;
                $roomCont->amount = ($gift->price * $request->quantity) * 0.1;
                $roomCont->save();
            }


            //Update room object
            if ($gift->type != 2){
                $room->contributions_value += $gift->price * $request->quantity;
                $room->update_contributions_value_at= Carbon::now('UTC');
                $room->save();
            }else{
                $room->contributions_value += ($gift->price * $request->quantity) * 0.1;
                $room->update_contributions_value_at= Carbon::now('UTC');
                $room->save();

            }


            //Upgrade room ranking HERE(IF REQUIRED)
            $vip = VipUser::find($sender->vip);

            //Fire Events
            GiftSent::dispatch($gift, $sender->name, $sender->profile_picture, $reciever->name, $reciever->profile_image, $request->quantity, $recieverChair->index, $room->id, $allWinMultiply,$vip);

            //Update Room ranking(if required)

            //Global Rooms Bar


            if($gift->price * $request->quantity >= 1000){
                GlobalGiftBar::dispatch($gift->cover, $gift->price, $request->quantity, $sender->name, $sender->profile_image, $reciever->name, $reciever->profile_image, $room->id, $room->pusher_channel_name, $room->agora_channel_name, 0, $allWinMultiply,  $vip );
            }

            //Handle Carizma counter
            if($recieverChair->is_carizma_counter == 1){

                if($gift->type != 2){
                    $recieverChair->carizma_counter+= $gift->price * $request->quantity;
                }else{
                    $recieverChair->carizma_counter+= ($gift->price * $request->quantity) * 0.1;
                }

                if($recieverChair->save()){
                    $newRecieverChair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($recieverChair->id);

                    UpdateChair::dispatch($newRecieverChair, 0, $room->id);
                }
            }

            $message = 'أرسل ' . ' x' . $request->quantity . ' ' . $gift->name . ' إلى ' . $reciever->name;
            $vip = VipUser::find( $sender->vip);
            RoomChatEvent::dispatch($message, 2, $room->id, $sender->id, $sender->name, $sender->profile_picture, $vip,  $sender->level_id );

            //Handle user level
            try {
                $sender->exp_points += $gift->price * $request->quantity;
                $sender->supported_send = $gift->price * $request->quantity;
                $sender->save();
                $this->levelUserUp($sender);
            } catch (\Exception $e) {
                return $e;
            }



            try {
                $reciever->exp_points += ($gift->price * $request->quantity) * 0.5;
                $reciever->supported_recieve = $gift->price * $request->quantity;
                $reciever->save();
            } catch (\Exception $e) {
                return $e;
            }

            $this->levelUserUp($reciever);

            /* Hosting Agency */
           if($reciever->is_hosting_agent == 1 && $gift->type != 2 ){
                    $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();
                        if (!is_null($agencyMembership)){
                            HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => $gift->price * $request->quantity, 'created_at' => now(), 'updated_at' => now()]);

                                            $this->levelTargetUp($agencyMembership);
                        }

                }elseif($reciever->is_hosting_agent == 1 && $gift->type == 2){

                    $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();
                    if (!is_null($agencyMembership)){
                         HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => ($gift->price * $request->quantity) * 0.1, 'created_at' => now(), 'updated_at' => now()]);
                    $this->levelTargetUp($agencyMembership);
                    }
                    // return $this->successWithoutData($agencyMembership);


                }



            // return $this->success($sender->diamond_balance, 'تم إرسال الهدية بنجاح');
        }catch(QueryException $e){
            // return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function sendGiftToAll(Request $request, $id) //Room ID
    {
        $rules = [
            'gift_id' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric', 'min:1', 'max:1000'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $room = Room::findOrFail($id);
        $gift = Gift::findOrFail($request->gift_id);
        $sender = auth()->user();

        // if($gift->type == 2){
        //     return $this->error('لا يمكن إرسال هدية من هدايا التضاعف إلى الجميع، برجاء تحديد مستقبل واحد فقط.', 403);
        // }

        // if($gift->type == 2){
        //     $winMultiply = $this->multiplyGift($gift->price * $request->quantity, $reciever);
        // }
        $roomMember = RoomMember::where('user_id', $sender->id)->first();


        //Validate sender is in the same room
        if(!$sender->roomMember || $sender->roomMember->room_id != $room->id){
            return $this->error('أنت لست متواجد في نفس الغرفه يا فنان', 403);
        }

        $roomChairs = RoomChair::where('room_id', $room->id)->where('user_id', '!=', null)->get();

        if(count($roomChairs) == 0){
            return $this->error('الغرفة فارغة يبن عمي', 403);
        }

        //Validate sender Balance
        if($sender->diamond_balance < $gift->price * $request->quantity * count($roomChairs)){
            return $this->error('عفوا رصيدك لا يكفي', 403);
        }

        $recieverChairIndexes = array();

        try{
            // dd('ss');
            //Subtract sender balance
            $sender->diamond_balance -= $gift->price * $request->quantity * count($roomChairs);
            $sender->save();

            // all Diamond Recieve
            $allWinMultiply = 0;

            //Attach Gift to all users
            foreach($roomChairs as $chair)
            {

                $reciever = $chair->user;
                // $singleDiamondPrice = 0.00065;
                // $reciever->money+= $singleDiamondPrice * (($gift->price * $request->quantity) * 0.5);
                if($gift->type == 2){
                    $winMultiply = $this->multiplyGift($gift->price * $request->quantity, $reciever);
                    $allWinMultiply +=$winMultiply;
                }


                // //check if the user already has the same gift => increment it
                // $duplicateGift = $reciever->gifts()->where('gift_id', $gift->id);
                // if($duplicateGift->exists()){
                //     $duplicateGift = $duplicateGift->withPivot('quantity')->first();

                //     $reciever->gifts()->updateExistingPivot($duplicateGift->id, ['quantity' => $duplicateGift->pivot->quantity + $request->quantity]);
                // }else{
                //     $reciever->gifts()->attach([$gift->id => ['quantity' => $request->quantity, 'created_at' => now(), 'updated_at' => now()]]);
                // }

                //Update room contribution if
                if ($gift->type !=2 ){
                    $roomCont = new RoomContribution;
                    $roomCont->room_id = $room->id;
                    $roomCont->receiver_id = $reciever->id;
                    $roomCont->sender_id = $sender->id;
                    $roomCont->gift_id = $gift->id;
                    $roomCont->quantity = $request->quantity;
                    $roomCont->amount = $gift->price * $request->quantity;
                    $roomCont->save();

                }else{
                    $roomCont = new RoomContribution;
                    $roomCont->room_id = $room->id;
                    $roomCont->receiver_id = $reciever->id;
                    $roomCont->sender_id = $sender->id;
                    $roomCont->gift_id = $gift->id;
                    $roomCont->quantity = $request->quantity;
                    $roomCont->amount = ($gift->price * $request->quantity) * 0.1;
                    $roomCont->save();
                }

                // $reciever->diamond_balance+= $gift->price * 0.1;

                $reciever->exp_points += ($gift->price * $request->quantity) * 0.5;

                $reciever->supported_recieve = $gift->price * $request->quantity;
                                    $reciever->gold_balance +=  $gift->price * $request->quantity ;

                try{
                    $reciever->save();
                }catch (\Exception $e) {
                return $e;
            }


                $this->levelUserUp($reciever);



                //Update room object
                if ($gift->type != 2){
                    $room->contributions_value += $gift->price * $request->quantity;
                    $room->update_contributions_value_at= Carbon::now('UTC');
                    // $reciever->gold_balance +=  (($gift->price * $request->quantity) );
                    $room->save();
                }else{
                    $room->contributions_value += ($gift->price * $request->quantity) * 0.1;
                    $room->update_contributions_value_at= Carbon::now('UTC');
                    $room->save();
                }


                $recieverChairIndexes[] = $chair->index;

                //Handle Carizma counter
                if($chair->is_carizma_counter == 1){
                    if($gift->type != 2){
                        $chair->carizma_counter+= $gift->price * $request->quantity;
                        $chair->save();
                    }else{
                        $chair->carizma_counter+= ($gift->price * $request->quantity) * 0.1;
                        $chair->save();
                    }
                    $chair->save();
                }

                /* Hosting Agency */
                if($reciever->is_hosting_agent == 1 && $gift->type != 2 ){
                    $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();
                        if (!is_null($agencyMembership)){
                            HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => $gift->price * $request->quantity, 'created_at' => now(), 'updated_at' => now()]);

                                            $this->levelTargetUp($agencyMembership);
                        }

                }elseif($reciever->is_hosting_agent == 1 && $gift->type == 2){

                    $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();
                    if (!is_null($agencyMembership)){
                         HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => ($gift->price * $request->quantity) * 0.1, 'created_at' => now(), 'updated_at' => now()]);
                    $this->levelTargetUp($agencyMembership);
                    }
                    // return $this->successWithoutData($agencyMembership);


                }
            }
            $vip = VipUser::find( $sender->vip);
            if( $allWinMultiply >= 500){
                GlobalGiftBar::dispatch($gift->cover, $gift->price, $request->quantity, $sender->name, $sender->profile_image, $reciever->name, $reciever->profile_image, $room->id, $room->pusher_channel_name, $room->agora_channel_name, 2,$allWinMultiply, $vip );
            }


            // return $chair->withSum('userRecievedContributions', 'amount')->get();

            $updatedChairs = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->where('room_id', $room->id)->get();
            $room->save();
            UpdateChairsEvent::dispatch($updatedChairs, 0, $room->id);
            $vip = VipUser::find( $sender->vip);
            //Dispatch Event
            GiftSentToAll::dispatch($gift, $sender->name, $sender->profile_image, $request->quantity, $room->id, $allWinMultiply,$vip);

            $message = 'أرسل ' .' x' . $request->quantity . ' ' . $gift->name . ' إلى ' . 'الكل';

            RoomChatEvent::dispatch($message, 2, $room->id, $sender->id, $sender->name, $sender->profile_picture, $vip , $sender->level_id);

            //Update Room ranking(if required)

            //Global Rooms Bar
            if($gift->price * $request->quantity >= 1000){
                GlobalGiftBar::dispatch($gift->cover, $gift->price, $request->quantity, $sender->name, $sender->profile_image, $reciever->name, $reciever->profile_image, $room->id, $room->pusher_channel_name, $room->agora_channel_name, 0, $allWinMultiply, $vip);
            }

            //Handle user level
            $sender->exp_points += $gift->price * $request->quantity * count($roomChairs);
            $sender->supported_send = $gift->price * $request->quantity;

            $sender->save();
            $this->levelUserUp($sender);



            return $this->successWithoutData('تم إرسال الهدايا بنجاح');
        }catch(QueryException $e){
            //return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }

    }

    public function getChairCarizmaDetails($id) //Chair ID
    {
        $chair = RoomChair::findOrFail($id);

        if($chair->user_id == null){
            return $this->error('الكرسي فارغ', 422);
        }

        if($chair->is_carizma_counter == 0){
            return $this->error('عداد كاريزما الكرسي مغلق', 422);
        }

        $chairCarizmaDetails = RoomContribution::where('receiver_id', $chair->user_id)->where('created_at', '>=', $chair->carizma_opened_at)->with('sender', 'gift')->orderBy('id', 'DESC')->get();

        return $this->data($chairCarizmaDetails);
    }

    public function getRoomContributions($id)
    {
        $requestUser = auth()->user();
        $room = Room::findOrFail($id);
        $now = Carbon::now();
        $lastSaturday = $now->startOfWeek(Carbon::SATURDAY)->format('Y-m-d');
        $lastFriday = $now->endOfWeek(Carbon::FRIDAY)->format('Y-m-d');


        // date_default_timezone_set('Africa/Cairo');

        // الحصول على الوقت الحالي بتوقيت القاهرة
        $nowInCairo = Carbon::now('Africa/Cairo');

        //  $nowInCairo = Carbon::now();
        //  $todayInCairo = Carbon::today();
        //Daily Senders
        $dailyTop10Senders =  RoomContribution::where('room_id', $id)->whereDate('created_at', $nowInCairo)
        ->select(DB::raw('sender_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('sender_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalDailyTop10Senders = array();

        if($dailyTop10Senders){
            foreach($dailyTop10Senders as $sender){
                $senderAmount = RoomContribution::where('room_id', $id)->where('sender_id', $sender->sender_id)->whereDate('created_at', Carbon::today())->get('amount');
                $userObj = User::with('defaultFrame')->findOrFail($sender->sender_id);

                $totalAmount = 0;
                foreach($senderAmount as $val){
                    $totalAmount+= $val->amount;
                }
                $finalDailyTop10Senders[] = ['user' => $userObj, 'amount' => $totalAmount];
            }
        }

        //Weekly Senders
        $weeklyTop10Senders =  DB::table('room_contributions')->where('room_id', $id)->whereBetween('created_at', [$lastSaturday, $lastFriday])
        ->select(DB::raw('sender_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('sender_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalWeeklyTop10Senders = array();

        if ($weeklyTop10Senders) {
            foreach ($weeklyTop10Senders as $sender) {
                $senderAmount = RoomContribution::where('room_id', $id)
                    ->where('sender_id', $sender->sender_id)
                    ->whereBetween('created_at', [$lastSaturday, $lastFriday])
                    ->get('amount');

                // التحقق مما إذا كان sender_id موجوداً قبل استدعاء findOrFail
                if (User::where('id', $sender->sender_id)->exists()) {
                    $userObj = User::with('defaultFrame')->findOrFail($sender->sender_id);

                    $totalAmount = 0;
                    foreach ($senderAmount as $val) {
                        $totalAmount += $val->amount;
                    }
                    $finalWeeklyTop10Senders[] = ['user' => $userObj, 'amount' => $totalAmount];
                } else {
                    // إذا كان sender_id غير موجود في جدول users، يتم تخطي هذا السجل
                    continue;
                }
            }

        }

        //Daily recievers
        $dailyTop10Recievers =  DB::table('room_contributions')->where('room_id', $id)->whereDate('created_at', Carbon::today())
        ->select(DB::raw('receiver_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('receiver_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalDailyTop10Recievers = array();

        if ($dailyTop10Recievers) {
            foreach ($dailyTop10Recievers as $receiver) {
                $receiverAmount = RoomContribution::where('room_id', $id)
                    ->where('receiver_id', $receiver->receiver_id)
                    ->whereDate('created_at', Carbon::today())
                    ->get('amount');

                // التحقق مما إذا كان receiver_id موجوداً قبل استدعاء findOrFail
                if (User::where('id', $receiver->receiver_id)->exists()) {
                    $userObj = User::with('defaultFrame')->findOrFail($receiver->receiver_id);

                    $totalAmount = 0;
                    foreach ($receiverAmount as $val) {
                        $totalAmount += $val->amount;
                    }
                    $finalDailyTop10Recievers[] = ['user' => $userObj, 'amount' => $totalAmount];
                } else {
                    // إذا كان receiver_id غير موجود في جدول users، يتم تخطي هذا السجل
                    continue;
                }
            }
        }

        //Weekly recievers
        $weeklyTop10Recievers =  DB::table('room_contributions')->where('room_id', $id)->whereBetween('created_at', [$lastSaturday, $lastFriday])
        ->select(DB::raw('receiver_id'), DB::raw('SUM(amount) as sum'))
        ->groupBy('receiver_id')
        ->orderBy('sum', 'DESC')
        ->get()->take(10);

        $finalWeeklyTop10Recievers = array();

        if ($weeklyTop10Recievers) {
                foreach ($weeklyTop10Recievers as $receiver) {
                    $receiverAmount = RoomContribution::where('room_id', $id)
                        ->where('receiver_id', $receiver->receiver_id)
                        ->whereBetween('created_at', [$lastSaturday, $lastFriday])
                        ->get('amount');

                    // التحقق مما إذا كان receiver_id موجوداً قبل استدعاء findOrFail
                    if (User::where('id', $receiver->receiver_id)->exists()) {
                        $userObj = User::with('defaultFrame')->findOrFail($receiver->receiver_id);

                        $totalAmount = 0;
                        foreach ($receiverAmount as $val) {
                            $totalAmount += $val->amount;
                        }
                        $finalWeeklyTop10Recievers[] = ['user' => $userObj, 'amount' => $totalAmount];
                    } else {
                        // إذا كان receiver_id غير موجود في جدول users، يتم تخطي هذا السجل
                        continue;
                    }
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

        $data = ['dailyTop10Senders' => $finalDailyTop10Senders, 'weeklyTop10Senders' => $finalWeeklyTop10Senders, 'dailyTop10Recievers' => $finalDailyTop10Recievers, 'weeklyTop10Recievers' => $finalWeeklyTop10Recievers, 'finalRequestUserDailySending' => $finalRequestUserDailySending, 'finalRequestUserWeeklySending' => $finalRequestUserWeeklySending, 'finalRequestUserDailyRecieved' => $finalRequestUserDailyRecieved, 'finalRequestUserWeeklyRecieved' => $finalRequestUserWeeklyRecieved];

        return $this->data($data);
    }

    public function getEmojis()
    {
        $emojis = Emoji::orderBy('id', 'DESC')->get();

        return $this->data($emojis);
    }

    public function sendEmoji($chairID, $emojiID) //room_id
    {
        $chair = RoomChair::findOrFail($chairID);
        $user = auth()->user();
        $emoji = Emoji::findOrFail($emojiID);

        //Validate that the request user is the one on the chair
        if($chair->user_id != $user->id){
            return $this->error('أنت غير متواجد على الكرسي', 403);
        }

        EmojiSent::dispatch($chair, $emoji, $chair->room_id);

        return $this->successWithoutData('تم إرسال الإيموجي');
    }

    public function getAllRoomBackgrounds()
    {
        $user= auth()->user();
        $currentRoom = RoomMember::where('user_id', $user->id)->with('room')->first();
        $idRoom = $currentRoom->room->rid;
        $backgroundsWithRoom = RoomBackground::where('room_id', $idRoom);
        $backgroundsWithoutRoom = RoomBackground::where('room_id', 0);

        $backgrounds = $backgroundsWithRoom->union($backgroundsWithoutRoom)->get();        // $backgrounds = RoomBackground::all();

        return $this->data($backgrounds);
    }

    public function sendLuckyBags(Request $request, $id) //ID of the parent bag(GIFT)
    {
        $rules = [
            'room_id' => ['required', 'numeric'],
        ];

        $room = Room::findOrFail($id);
        $user = auth()->user();

        //Check if the user Balance able to handle the bag price

        //Check if the user is in the same room

        //Prepare the Bags

        //Broadcast the event
    }

    protected function levelUserUp($user)
    {
        $currentLevel = $user->currentLevel()->first(); //????

        $nextLevel = Level::where('number', $currentLevel->number + 1)->first();

        if( $user->exp_points >= $nextLevel->required_exp)
        {
            $user->level_id = $nextLevel->id;

            // if($user->exp_points > $nextLevel->required_exp){
            //     $user->exp_points = $user->exp_points - $nextLevel->required_exp;
            // }else{
            //     $user->exp_points = 0;
            // }

            // if($user->level_id == 19){

            // }

            $user->save();



            $this->levelUserUp($user);
        }else{
            return;
        }
    }

    protected function levelTargetUp($membership)
    {

        // $memberBd= HostingAgencyMemberBd::where('hosting_agency_id', $membership->hosting_agency_id)->with('target', 'user')->first();
        // // dd($memberBd);
        // $salary = 0;
        // if(!is_null($memberBd)){

        //     $nextTargetBd = HostingAgencyTargetBd::where('target_no',$memberBd->target->target_no + 1)->first();
        //     // dd($nextTargetBd->salary_required);
        //     $memberBds = HostingAgencyMemberBd::where('user_id', $memberBd->user_id)->with('agency')->get();
        //     $salary = 0;
        //     foreach($memberBds as $memberBd){
        //         $membersAgency = HostingAgencyMember::where('hosting_agency_id', $memberBd->agency->id)->with('target')->get();

        //         foreach($membersAgency as $memberAgency){
        //             $salary += $memberAgency->target->salary;
        //             $salary += $memberAgency->target->owner_salary;
        //         }
        //     }

        // if(!is_null($nextTargetBd)){
        //     if($salary >= $nextTargetBd->salary_required){
        //         $memberBd->current_target_id = $nextTargetBd->id;
        //         $memberBd->save();
        //         $this->levelTargetUp($membership);
        // }}
        // }



        // make target 1 if today is 1 of the mounth
        // $currentDate = Carbon::now();



        $currentTarget = $membership->target()->first();

        $targetMember=HostingAgencyMember::where('user_id', $membership->user_id)->first();
        // if user in agency  and member no null >> give gift to receiver he desevered it

        if(!is_null($targetMember)){
            if($targetMember->current_target_id == 4){

                if ($targetMember->user->supported_recieve >= 90000 && $targetMember->user->supported_recieve < 120000) {
                    if($targetMember->user->vip < 1){
                        $targetMember->user->vip = 1;
                        $targetMember->user->save();
                    }

                } elseif ($targetMember->user->supported_recieve >= 120000) {
                    if($targetMember->user->vip < 2){
                        $targetMember->user->vip = 2;
                        $targetMember->user->save();
                    }
                };

            };
        };

        $user = User::findOrFail($membership->user_id);

        // give to sender the gift
        if ($user->supported_send >= 1000000 && $user->supported_send < 1500000) {
            if($user->vip < 4){
                $user->vip = 4;
            }
        } elseif ($user->supported_send >= 1500000 && $user->supported_send < 2000000) {
            if($user->vip < 4){
                $user->vip = 4;
            }
        } elseif ($user->supported_send >= 2000000 && $user->supported_send < 2500000) {
            if($user->vip < 5){
                $user->vip = 5;
            }
        } elseif ($user->supported_send >= 2500000 && $user->supported_send < 3000000) {
            if($user->vip < 5){
                $user->vip = 5;
            }
        } elseif ($user->supported_send >= 3000000 && $user->supported_send < 3500000) {
            if($user->vip < 6){
                $user->vip = 6;
            }
        } elseif ($user->supported_send >= 3500000) {
            if($user->vip < 6){
                $user->vip = 6;
            }
        }
        $user->save();


        $nextTarget = HostingAgencyTarget::where('target_no', $currentTarget->target_no + 1)->first();


        if($nextTarget && HostingAgencyDiamondPerformance::where('agency_member_id', $membership->id)->sum('amount') >= $nextTarget->diamonds_required ){
        //if you need to add hoursrequired add this to if condition => (&& HostingAgencyHourPerformance::where('agency_member_id', $membership->id)->sum('duration') >= $nextTarget->hours_required)
            $membership->current_target_id = $nextTarget->id;
            $membership->save();

            $this->levelTargetUp($membership);
        }else{
            return;
        }
    }

    protected function multiplyGift($bit, $host)
    {
        // $globalBox = GlobalBox::find(1);
        $user = auth()->user();

        //increament times played
        // $globalBox->times_played+=1;
        // $globalBox->save();

        //Give host 20%
        // $singleDiamondPrice = 0.00075;
        // $host->money+= $singleDiamondPrice * ($bit * 0.2);

        // $host->diamond_balance += $bit * 0.1;
        // $host->save();

        //check global box's balance
        // if($globalBox->amount < $bit * 2){ // bit * 2 => x1
        //     $globalBox->amount+= $bit * 0.7;
        //     $globalBox->in_box+= $bit * 0.7;

        //     $globalBox->save();

        //     return 0;
        // }

        //get a lose or a win 2/3
        // $winOrLosePossibilities = [1, 2, 3, 4, 5];
        // if(Arr::random($winOrLosePossibilities) != 1){
        //     $globalBox->amount+= $bit * 0.7;
        //     $globalBox->in_box+= $bit * 0.7;
        //     $globalBox->save();

        //     return 0;
        // }

//         $probability = rand(1, 1000) / 10; // Generate a number between 0.1 and 100

// if ($probability <= 30) {
//     // 30% probability: return 80% of gift price
//     $user->diamond_balance += $bit * 0.8;
//     $winMultiply = $bit * 0.8;
// } elseif ($probability <= 55) {
//     // 25% probability: return 100% of gift price
//     $user->diamond_balance += $bit * 1;
//     $winMultiply = $bit * 1;
// } elseif ($probability <= 75) {
//     // 20% probability: return 200% of gift price
//     $user->diamond_balance += $bit * 2;
//     $winMultiply = $bit * 2;
// } elseif ($probability <= 90) {
//     // 15% probability: return 300% of gift price
//     $user->diamond_balance += $bit * 3;
//     $winMultiply = $bit * 3;
// } elseif ($probability <= 97) {
//     // 7% probability: return 400% of gift price
//     $user->diamond_balance += $bit * 4;
//     $winMultiply = $bit * 4;
// } elseif ($probability <= 99) {
//     // 2% probability: return 600% of gift price
//     $user->diamond_balance += $bit * 6;
//     $winMultiply = $bit * 6;
// } elseif ($probability <= 99.5) {
//     // 0.5% probability: return 1000% of gift price
//     $user->diamond_balance += $bit * 10;
//     $winMultiply = $bit * 10;
// } elseif ($probability <= 99.8) {
//     // 0.3% probability: return 1500% of gift price
//     $user->diamond_balance += $bit * 15;
//     $winMultiply = $bit * 15;
// } elseif ($probability <= 99.9) {
//     // 0.1% probability: return 2000% of gift price
//     $user->diamond_balance += $bit * 20;
//     $winMultiply = $bit * 20;
// } elseif ($probability <= 99.95) {
//     // 0.05% probability: return 5000% of gift price
//     $user->diamond_balance += $bit * 50;
//     $winMultiply = $bit * 50;
// } else {
//     // 0.05% probability: return 10000% of gift price
//     $user->diamond_balance += $bit * 100;
//     $winMultiply = $bit * 100;
// }

// Array of possible win multipliers
// $winMultipliers = [10, 50, 100, 200, 400, 500, 700, 900, 1000, 1500, 2000, 2500, 3000, 3500, 4000, 5000, 10000];

// Generate a number between 0.1 and 100
// Array of possible win multipliers
$winMultipliers = [
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
    10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10,
    20, 20, 20, 20, 20, 20, 20, 20,
    50, 50, 50, 50, 50, 50, 50, 50, 50, 50,
    100, 100, 100, 100, 100,
    200, 200, 200, 200, 200, 200, 200,
    400, 400,
    500, 500, 500, 500,
    1000, 1000,
    1500, 1500,
    2000,
    2500, 2500,
    700, 700, 900, 900, 3000, 3500, 4000, 5000, 10000
];
// $winMultipliers = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,10, 10,10,10,10,10, 10,10,10,10,10, 10,10,10,10,10, 10,10,10,10,10, 10,10,10,10,10, 10,10,10,10,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,20,30,30,30,30,30,40,40,40,40,40,50, 100,100,100,100, 200,200,200,200, 400, 500, 700, 900, 1000, 1500, 2000, 2500, 3000, 3500, 4000, 5000, 10000];

// Randomly pick a win multiplier from the array
$winMultiply = $winMultipliers[array_rand($winMultipliers)];

// Update the user's diamond balance based on the randomly chosen win multiplier
$user->diamond_balance += $bit * ($winMultiply / 100);


// Update the user's diamond balance
// $user->diamond_balance += $bit * ($winMultiply / 100);



        // Update global box's balance


        // if($winMultiply == 99.9){
        //     if(Carbon::parse($globalBox->last_500)->format('Y-m-d') == Carbon::parse(now())->format('Y-m-d')){
        //         $winMultiply;
        //     }
        // }

        // $user->diamond_balance+= $bit * $winMultiply;

        // $globalBox->amount-= $bit * $winMultiply;
        // $globalBox->out_box+= $bit * $winMultiply;
        // $globalBox->save();

        return $winMultiply;
    }

}
