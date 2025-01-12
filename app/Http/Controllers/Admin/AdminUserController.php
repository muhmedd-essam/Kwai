<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Rooms\Room;
use App\Models\Rooms\RoomMember;
use App\Models\Rooms\RoomChair;
use App\Events\RoomChatEvent;
use App\Events\RoomMembersUpdate;
use App\Events\UpdateChair;
use App\Models\VipUser;

class AdminUserController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('country', 'followings.followingUser', 'followers.followerUser', 'friends.friendUser')->paginate(12);

        return $this->data($users);
    }

    public function indexWithoutRelation()
    {
        $users = User::all();

        return $this->data($users);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with('country', 'followings.followingUser', 'followers.followerUser', 'friends.friendUser')->findOrFail($id);

        return $this->data($user);
    }

    public function search(Request $request)
    {
        $rules = [
            'uid' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $users = User::where('uid', 'like', '%' . $request->uid . '%')->orderBy('uid', 'ASC')->get(['id', 'name', 'uid', 'profile_picture']);

        return $this->data($users);
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
        $user = User::findOrfail($id);

        $rules = [
            // "provider_id" => ['numeric'],
            // "provider_name" => ['string', 'in:facebook,google'],
            "name" => ['string', 'min:2', 'max:150'],
            "email" => ['email'],
            "profile_img" => ['mimes:jpeg,png,jpg,gif,max:2048'],
            "phone" => ['min:10', 'max:14','unique:users,phone,'.$user->id],
            'dob' => ['date', 'before:01-01-2005'],
            "gender" => ['string', 'in:male,female'],
            'about_me' => ['string', 'min:3', 'max:1000'],
            'country_id' => ['numeric', 'exists:countries:id'],
            'money' => ['numeric',],
            'vip' => ['numeric'],
            'vip_time' => ['numeric']
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        if($request->hasFile('profile_img')){
            $profileImage = Storage::disk('public')->putFile('images/users/profile_images', new File($request->profile_img));
            $request->request->add(['profile_picture' => $profileImage]);
        }

        try{
            $user->update($request->all());

            if(isset($request->money)){
                $user->money = $request->money;
                $user->save();
            }

            return $this->success('S101', '');
        }catch(QueryException $e){
            //return $e;
            return $this->error('E200', '');
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
        $user = User::findOrFail($id);

        $user->delete();

        return $this->success('S103');
    }

    /**
     * Block certain user by id
     */
    public function block(Request $request ,$id) //ID of user
    {
        $user = User::findOrFail($id);

        $rules = [
            'permenant' => ['required', 'in:0,1'],
            'block_until' => ['required_if:permenant,0', 'date', 'after:now'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        if($request->permenant == 1){
            $date = '3000-01-01';
        }else{
            $date = $request->block_until;
        }

        if($user->roomMember != null){
            $room = Room::findOrFail($user->roomMember->room_id);

            if(RoomChair::where('user_id', $user->id)->exists()){
                $userChair = RoomChair::where('user_id', $user->id)->first();
                $userChair->user_id = null;
                $userChair->is_muted_by_user = 0;
                $userChair->save();

                //Events
                $updatedChair = RoomChair::with('user.defaultFrame', 'user.nextLevel')->withSum('userRecievedContributions', 'amount')->withSum('userSentContributions', 'amount')->withCount('userFollowers')->findOrFail($userChair->id);
                UpdateChair::dispatch($updatedChair, 1, $room->id);
            }

            $newRoomMembers = RoomMember::where('room_id', $room->id)->get();

            $newMember = RoomMember::where('user_id', $user->id)->with('user.defaultFrame', 'user.defaultEntry')->first();
            RoomMembersUpdate::dispatch($newMember, 0,count($newRoomMembers) - 1, $room->id);

            $message = $user->name . " تم حظره من التطبيق";
            $vip = VipUser::find($user->vip);
            RoomChatEvent::dispatch($message, $room->id, $user->id, $user->name, $user->profile_picture, $vip, $user->level_id);

            $user->roomMember->delete();
        }
        try{
            $user->deactivated_until = $date;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            //return $e;
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function unblock($id) //ID of user
    {
        $user = User::findOrFail($id);

        try{
            $user->deactivated_until = null;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function insertSupporter($id){
        $user = User::findOrFail($id);
        try{
            $user->is_video_hosting = 1;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function deleteSupporter($id){
        $user = User::findOrFail($id);
        try{
            $user->is_video_hosting = 0;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function insertSuperAdmin($id){
        $user = User::findOrFail($id);
        try{
            $user->is_group_owner = 1;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function deleteSuperAdmin($id){
        $user = User::findOrFail($id);
        try{
            $user->is_group_owner = 0;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function insertAdmin($id){
        $user = User::findOrFail($id);
        try{
            $user->is_video_cohosting = 1;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function deleteAdmin($id){
        $user = User::findOrFail($id);
        try{
            $user->is_video_cohosting = 0;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }
}
