<?php

namespace App\Http\Controllers\Api\Rooms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VipUser;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\Rooms\Room;
use App\Models\Rooms\RoomChair;
use App\Models\Rooms\RoomMember;
use App\Events\UpdateChair;
use App\Events\UpdateChairsEvent;
use App\Events\RoomMembersUpdate;
use App\Events\RoomChatEvent;
use App\Events\RoomMicInvite;
use App\Events\RoomMemberUpdate;
use App\Events\MicInviteAccepted;
use App\Models\Rooms\RoomBlock;
use App\Models\Rooms\MicInvite;
use App\Models\Rooms\RoomModerator;

class RoomOwnerController extends Controller
{
    use MobileTrait;

    public function changeChairStatus(Request $request, $id) //Chair ID
    {
        $chair = RoomChair::with('user.defaultEntry', 'user.defaultFrame')->findOrFail($id);
        $user = auth()->user();

        $rules = [
            'type' => ['required', 'in:lock,mic'],
            'status' => ['required', 'in:0,1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        //Validate that the request sender is the room owner
        if($chair->room->owner_id != $user->id){
            if(!$this->checkIfModerator($chair->room, $user)){
                return $this->error('you\'re not the owner or a moderator in this room', 403);
            }
        }

        try{
            if($request->type == 'lock'){
                if($request->status == 0){
                    $chair->is_locked = 0;
                    $chair->save();

                    $newChair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($chair->id);
                    UpdateChair::dispatch($newChair, 0, $chair->room->id);
                    return $this->successWithoutData('تم فتح الكرسي بنجاح');
                }elseif($request->status == 1){
                    $chair->is_locked = 1;
                    $chair->save();

                    $newChair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($chair->id);
                    UpdateChair::dispatch($newChair, 0, $chair->room->id);
                    return $this->successWithoutData('تم غلق الكرسي بنجاح');
                }
            }elseif($request->type == 'mic'){
                if($request->status == 0){
                    $chair->is_muted = 0;
                    $chair->save();

                    $newChair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($chair->id);
                    UpdateChair::dispatch($newChair, 0, $chair->room->id);
                    return $this->successWithoutData('تم فتح المايك بنجاح');
                }elseif($request->status == 1){
                    $chair->is_muted = 1;
                    $chair->save();

                    $newChair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($chair->id);
                    UpdateChair::dispatch($newChair, 0, $chair->room->id);
                    return $this->successWithoutData('تم كتم المايك بنجاح');
                }
            }

        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function changeAllChairsStatus(Request $request, $id) //Room ID
    {
        $room = Room::findOrFail($id);
        $user = auth()->user();

        $rules = [
            'type' => ['required', 'in:lock,mic'],
            'status' => ['required', 'in:0,1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $chairs = RoomChair::where('room_id', $room->id)->get();

        //Validate that the request sender is the room owner
        if($room->owner_id != $user->id){
            if(!$this->checkIfModerator($room, $user)){
                return $this->error('you\'re not the owner or a moderator in this room', 403);
            }
        }

        try{
            if($request->type == 'lock'){
                if($request->status == 0){
                    foreach($chairs as $chair){
                        $chair->is_locked = 0;
                        $chair->save();
                    }

                    $newChairs = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->where('room_id', $room->id)->get();
                    UpdateChairsEvent::dispatch($newChairs, 0, $room->id);

                    return $this->successWithoutData('تم فتح كل الكراسي بنجاح');
                }elseif($request->status == 1){
                    foreach($chairs as $chair){
                        $chair->is_locked = 1;
                        $chair->save();
                    }

                    $newChairs = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->where('room_id', $room->id)->get();
                    UpdateChairsEvent::dispatch($newChairs, 0, $room->id);

                    return $this->successWithoutData('تم غلق كل الكراسي بنجاح');
                }
            }elseif($request->type == 'mic'){
                if($request->status == 0){
                    foreach($chairs as $chair){
                        $chair->is_muted = 0;
                        $chair->save();
                    }

                    $newChairs = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->where('room_id', $room->id)->get();
                    UpdateChairsEvent::dispatch($newChairs, 0, $room->id);

                    return $this->successWithoutData('تم فتح كل المايكات بنجاح');
                }elseif($request->status == 1){
                    foreach($chairs as $chair){
                        $chair->is_muted = 1;
                        $chair->save();
                    }

                    $newChairs = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->where('room_id', $room->id)->get();
                    UpdateChairsEvent::dispatch($newChairs, 0, $room->id);

                    return $this->successWithoutData('تم كتم كل المايكات بنجاح');
                }
            }
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function kickUser(Request $request, $id) //Room ID
    {
        $rules = [
            'user_id' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $requestUser = auth()->user();
        $user = User::findOrFail($request->user_id);
        $room = Room::findOrFail($id);

        //Validate that the user is not the owner
        if($request->user_id == $requestUser->id){
            return $this->error('هل حقا تريد حظر نفسك؟؟!!', 403);
        }

        //Validate that the user is in the room
        if($user->roomMember == null){
            return $this->error('هذا الشخص ليس في الغرفة أصلا', 403);
        }else{
            if($user->roomMember->room->id != $room->id){
                return $this->error('هذا الشخص متواجد في غرفة اخرى', 403);
            }
        }

        //Validate that the request user is the owner of the room
        if($room->owner_id != $requestUser->id){
            if(!$this->checkIfModerator($room, $requestUser)){
                return $this->error('you\'re not the owner or a moderator in this room', 403);
            }
        }

        //Kick user of a chair(if on one)
        foreach($room->chairs as $chair){
            if($chair->user_id == $user->id){
                $chair->user_id = null;
                $chair->is_muted_by_user = 0;
                $chair->carizma_counter = 0;
                $chair->carizma_opened_at = null;
                $chair->save();

                //Fire Event
                $updatedChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($chair->id);
                UpdateChair::dispatch($updatedChair, 1, $room->id);
            }
        }

        try{
            $member = $user->roomMember;
            $oldMember = $member;
            $member->delete();

            $block = new RoomBlock;
            $block->room_id = $room->id;
            $block->user_id = $user->id;
            $block->save();

            $newRoomMembers = RoomMember::where('room_id', $room->id)->get();

            RoomMembersUpdate::dispatch($oldMember, 2, count($newRoomMembers), $room->id);
            $vip = VipUser::find( $user->vip);

            $message = "تم حظر " . $user->name . "من الغرفة";
            RoomChatEvent::dispatch($message, 5, $room->id, $user->id, $user->name, $user->profile_picture, $vip, $user->level_id);

            return $this->successWithoutData('تم طرد العضو من الغرفة، وتم حظره');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }

    }

    public function unBlockUser(Request $request, $id) //Room ID
    {
        $rules = [
            'user_id' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $requestUser = auth()->user();
        $user = User::findOrFail($request->user_id);
        $room = Room::findOrFail($id);

        $block = RoomBlock::where('room_id', $room->id)->where('user_id', $user->id);

        //Validate that the request user is the owner of the room
        if($room->owner_id != $requestUser->id){
            if(!$this->checkIfModerator($room, $requestUser)){
                return $this->error('you\'re not the owner or a moderator in this room', 403);
            }
        }

        if(!$block->exists()){
            return $this->error('هذا الشخص غير محظور أصلا', 403);
        }

        try{
            $block->delete();

            return $this->successWithoutData('تم فك الحظر');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function kickUserOfChair(Request $request, $id) //Room ID
    {
        $rules = [
            'chair_id' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $requestUser = auth()->user();
        $room = Room::findOrFail($id);
        $chair = RoomChair::findOrFail($request->chair_id);

        // if($chair->index == 0){
        //     return $this->error('لا يمكن مغادرة كرسي صاحب الغرفة', 403);
        // }

        //Validate that the request user is the owner of the room
        if($room->owner_id != $requestUser->id){
            if(!$this->checkIfModerator($room, $requestUser)){
                return $this->error('you\'re not the owner or a moderator in this room', 403);
            }
        }

        //Validate that the chair is in the same room
        if($chair->room_id != $room->id){
            return $this->error('Chair is not in the same room', 403);
        }

        //Validate chair is not Empty
        if($chair->user_id == null){
            return $this->error('الكرسي فارغ', 403);
        }

        try{
            $chair->user_id = null;
            $chair->is_muted_by_user = 0;
            $chair->carizma_counter = 0;
            $chair->carizma_opened_at = null;
            $chair->save();

            $updatedChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($chair->id);
            UpdateChair::dispatch($updatedChair, 1, $room->id);

            return $this->successWithoutData('تم إنزال هذا الشخص من على الكرسي');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }

    }

    public function inviteUserToMic(Request $request, $id) //Room ID
    {
        $rules = [
            'chair_id' => ['required'],
            'user_id' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $requestUser = auth()->user();
        $room = Room::findOrFail($id);
        $chair = RoomChair::findOrFail($request->chair_id);
        $user = User::findOrFail($request->user_id);

        //Validate that the request user is the owner of the room
        if($room->owner_id != $requestUser->id){
            if(!$this->checkIfModerator($room, $requestUser)){
                return $this->error('you\'re not the owner or a moderator in this room', 403);
            }
        }

        //Validate that the chair is in the same room
        if($chair->room_id != $room->id){
            return $this->error('Chair is not in the same room', 403);
        }

        //Validate chair is Empty
        if($chair->user_id != null){
            return $this->error('الكرسي غير فارغ', 403);
        }

        //Validate user is in the same room
        if($user->roomMember == null){
            return $this->error('المستخدم غير متواجد في أي غرفة', 403);
        }else{
            if($user->roomMember->room->id != $room->id){
                return $this->error('المستخدم غير متواجد في هذه الغرفة', 403);
            }
        }

        //Check if user is on another chair
        foreach($room->chairs as $roomChair){
            if($roomChair->user_id == $user->id){
                return $this->error('المستخدم بالفعل متواجد على كرسي اخر', 403);
            }
        }

        //Check if the owner invites himself
        if($requestUser->id == $user->id){
            return $this->error('لا يمكنك دعوة نفسك يا ولد', 403);
        }

        try{
            $invite = new MicInvite;
            $invite->room_id = $room->id;
            $invite->user_id = $user->id;
            $invite->room_chair_id = $chair->id;
            $invite->save();

            //Fire Event
            RoomMicInvite::dispatch($invite, $room->id);

            return $this->successWithoutData('تم إرسال الدعوة بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function acceptOrDeclineMicInvite(Request $request, $id)
    { //Invite ID
        $rules = [
            'status' => ['required', 'in:0,1'] //0 => Decline, 1 => Accept
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $user = auth()->user();
        $invite = MicInvite::findOrFail($id);
        $room = $invite->room;

        //Validate Invite is for the same user
        if($invite->user_id != $user->id){
            return $this->error('هذه الدعوة غير موجهة لك', 403);
        }

        //Validate user is in the same room
        if($user->roomMember == null){
            return $this->error('أنت غير متواجد في أي غرفة', 403);
        }else{
            if($user->roomMember->room->id != $room->id){
                return $this->error('أنت غير متواجد في هذه الغرفة', 403);
            }
        }

        //Check if user is on another chair
        foreach($room->chairs as $roomChair){
            if($roomChair->user_id == $user->id){
                return $this->error('أنت بالفعل متواجد على كرسي اخر', 403);
            }
        }

        //Validate Chair is not empty
        if($invite->chair->user_id != null){
            return $this->error('الكرسي غير فارغ', 403);
        }

        try{
            if($request->status == 1){
                $invite->chair->user_id = $user->id;
                $invite->chair->save();

                $updatedChair = RoomChair::with('user.defaultFrame', 'user.level')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($invite->chair->id);

                UpdateChair::dispatch($updatedChair, 1, $room->id);

                MicInviteAccepted::dispatch($updatedChair, 1, $room->id);
                

                $invite->delete();
                

                return $this->successWithoutData('تم الصعود على الكرسي بنجاح');
            }
            // UpdateChair::dispatch($updatedChair, 1, $room->id);

            $invite->delete();
            return $this->successWithoutData('تم رفض الدعوة');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function makeModerator(Request $request, $id) //Room ID
    {
        $rules = [
            'user_id' => ['required', 'numeric', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $roomOwner = auth()->user();
        $room = Room::findOrFail($id);
        $moderatorToBe = User::findOrFail($request->user_id);

        /* Manual Validation */
        //Validate request user is the room owner
        if($room->owner_id != $roomOwner->id){
            return $this->error('هذا القرار متروك فقط لصاحب الغرفة', 403);
        }

        //Validate moderator to be is not the room owner
        if($room->owner_id == $moderatorToBe->id){
            return $this->error('لا يمكنك تعيين صاحب الغرفة كمشرف', 403);
        }

        //Validate user is not already a moderator
        if(RoomModerator::where('room_id', $room->id)->where('user_id', $moderatorToBe->id)->exists())
        {
            return $this->error('هذا الشخص بالفعل مشرف', 403);
        }

        //Validate moderator is a member in this room
        $flag = 0;
        foreach($room->members as $member){
            if($member->user_id == $moderatorToBe->id){
                $flag = 1;
            }
        }
        if($flag == 0){
            return $this->error('الشخص المراد تعيينه كمشرف غير متواجد بالغرفة', 403);
        }
        /* Manual Validation ENDS */

        try{
            $roomModerator = new RoomModerator;
            $roomModerator->user_id = $moderatorToBe->id;
            $roomModerator->room_id = $room->id;
            $roomModerator->save();

            $moderatorToBe->roomMember->is_moderator = 1;
            $moderatorToBe->roomMember->save();

            //Fire Event:
            RoomMemberUpdate::dispatch($moderatorToBe->roomMember, $room->id);

            return $this->successWithoutData('تم تعيين مشرف الجديد بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function removeModerator($id)
    {
        $roomModerator = RoomModerator::findOrFail($id);
        $roomOwner = auth()->user();

        //Validate request user is the room owner
        if($roomModerator->room->owner_id != $roomOwner->id){
            return $this->error('هذا القرار متروك فقط لصاحب الغرفة', 403);
        }

        try{
            $roomModerator->delete();

            if($roomModerator->user->roomMember != null){
                $roomModerator->user->roomMember->is_moderator = 0;
                $roomModerator->user->roomMember->save();
                //Fire Event:
                RoomMemberUpdate::dispatch($roomModerator->user->roomMember, $roomModerator->user->roomMember->room_id);
            }

            return $this->successWithoutData('تم حذف المشرف بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    public function changeCarizmaCounterStatus(Request $request, $id) //Chair ID
    {
        $chair = RoomChair::withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->findOrFail($id);
        $requestUser = auth()->user();
        $room = Room::findOrFail($chair->room_id);

        $rules = [
            'status' => ['required', 'numeric', 'in:0,1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($requestUser->id != $room->owner_id){
            if(!checkIfModerator($room, $requestUser)){
                return $this->error('يجب أن تكون صاحب الغرفة أو أحد المشرفيين', 403);
            }
        }

        try{
            $chair->is_carizma_counter = (int)$request->status;

            //reset if locking
            if($request->status == 0){
                $chair->carizma_counter = 0;
                $chair->carizma_opened_at = null;
            }else{ //start counter date
                $chair->carizma_opened_at = now();
            }

            $chair->save();

            UpdateChair::dispatch($chair, 0, $room->id);

            return $this->successWithoutData('تم تغيير حالة كاريزما الكرسي بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);

        }
    }

    public function changeCarizmaCounterStatusForAllChairs(Request $request, $id) //Room ID
    {
        $room = Room::findOrFail($id);
        $requestUser = auth()->user();

        $rules = [
            'status' => ['required', 'numeric', 'in:0,1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($requestUser->id != $room->owner_id){
            if(!checkIfModerator($room, $requestUser)){
                return $this->error('يجب أن تكون صاحب الغرفة أو أحد المشرفيين', 403);
            }
        }

        try{
            foreach($room->chairs as $chair){
                $chair->is_carizma_counter = (int)$request->status;

                //reset if locking
                if($request->status == 0){
                    $chair->carizma_counter = 0;
                    $chair->carizma_opened_at = null;
                }else{ //start counter date
                    $chair->carizma_opened_at = now();
                }

                $chair->save();
            }

            $updatedChairs = RoomChair::where('room_id', $room->id)->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->get();

            UpdateChairsEvent::dispatch($updatedChairs, 0, $room->id);

            return $this->successWithoutData('تم تغيير حالة كاريزما الكراسي بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }

    }

    public function resetCarizmaCounterForAllChairs($id)
    {
        $requestUser = auth()->user();
        $room = Room::findOrFail($id);

        if($requestUser->id != $room->owner_id){
            if(!checkIfModerator($room, $requestUser)){
                return $this->error('يجب أن تكون صاحب الغرفة أو أحد المشرفيين', 403);
            }
        }

        try{
            foreach($room->chairs as $chair){
                $chair->carizma_counter = 0;
                $chair->save();
            }

            $updatedChairs = RoomChair::where('room_id', $room->id)->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->get();

            UpdateChairsEvent::dispatch($updatedChairs, 0, $room->id);

            return $this->successWithoutData('تم تصفير كاريزما الكراسي بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة مجددا', 500);
        }
    }

    protected function checkIfModerator($room, $user)
    {
        foreach($room->moderators as $moderator)
        {
            if($user->id == $moderator->user_id){
                return 1;
            }
        }

        return 0;
    }

}
