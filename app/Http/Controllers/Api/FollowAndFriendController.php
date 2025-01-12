<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Friend;
use App\Models\Following;
use App\Models\Rooms\RoomMember;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FollowAndFriendController extends Controller
{
    use MobileTrait;

    public function getFollowers()
    {
        $user = User::with('followers.followerUser')->findOrFail(auth()->id());

        $followers = $user->followers;

        return $this->data($followers);
        //
    }

    public function getFollowings()
    {
        $user = User::with('followings.followingUser')->findOrFail(auth()->id());

        $followings = $user->followings;

        return $this->data($followings);
    }

    public function getFriends()
    {
        $user = User::with('friends.friendUser')->findOrFail(auth()->id());
        $friends = $user->friends;
        $allFriendsWithRoom=[];

        // استخراج بيانات friend_user لكل صديق
        foreach ($friends as $friend) {
            $friendUserData = $friend;
            $friendWithRoom = User::with('RoomMember.room')->findOrFail($friendUserData->friend_id);
            if ($friendWithRoom->RoomMember === null) {
                $friendWithRoom = User::findOrFail($friendUserData->friend_id);

                $allFriendsWithRoom[] = $friendWithRoom;
                continue;
            }
            $allFriendsWithRoom[] = $friendWithRoom;
        }

        return response()->json([
            'data' => $allFriendsWithRoom,
        ]);
    }

    /**
     * Follow User
     *
     */
    public function follow($id) //ID of Following(user to be followed)
    {
        $user = auth()->user();
        $followingUser = User::findOrFail($id);

        /* Manual Validation */
        if(Following::where('follower_id', $user->id)->where('following_id', $followingUser->id)->exists()){
            return $this->error('أنت تتابع هذا الشخص بالفعل!', 403);
        }
        if($id == $user->id){
            return $this->error('لا يمكنك متابعة نفسك ياعم!', 403);
        }
        /* Manual Validation ENDS */

        try{
            Following::create([
                'following_id' => $followingUser->id,
                'follower_id' => $user->id,
            ]);

            if(Following::where('follower_id', $followingUser->id)->where('following_id', $user->id)->exists()){
                Friend::create([
                    'user_id' => $user->id,
                    'friend_id' => $followingUser->id,
                ]);

                Friend::create([
                    'user_id' => $followingUser->id,
                    'friend_id' => $user->id,
                ]);
                return $this->successWithoutData('تمت المتابعة بنجاح، لقد أصبحتم أصدقاء الأن');
            }
            return $this->successWithoutData('تمت المتابعة بنجاح');
        }catch(QueryException $e){
            return $this->error('حدث خطأ ما برجاء المحاولة لاحقا!', 500);
        }
    }

    public function unfollow($id)
    {
        $user = auth()->user();
        $followingUser = User::findOrFail($id);

        /* Manual Validation */
        if($id == $user->id){
            return $this->error('لا يمكنك إلغاء متابعة نفسك ياعم!', 403);
        }
        /* Manual Validation ENDS */

        if(Following::where('follower_id', $user->id)->where('following_id', $followingUser->id)->exists()){
            $followRow = Following::where('follower_id', $user->id)->where('following_id', $followingUser->id)->first();

            //unfollow
            $followRow->delete();

            //Handle Friends
            if(Following::where('follower_id', $followingUser->id)->where('following_id', $user->id)->exists()){ //Already Friends
                //First SIDE friend row
                $friendRow = Friend::where('user_id', $user->id)->where('friend_id', $followingUser->id)->first();
                $friendRow->delete();

                //Other SIDE friend row
                $friendRow = Friend::where('user_id', $followingUser->id)->where('friend_id', $user->id)->first();
                $friendRow->delete();
            }

            return $this->successWithoutData('تم إلغاء المتابعة بنجاح');
        }
        return $this->error('أنت لا تتابع هذا الشخص حتى الأن', 403);
    }

}
