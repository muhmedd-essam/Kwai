<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hobby;
use App\Models\PhotoAlbum;
use App\Models\User;
use App\Models\VipUser;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class UserController extends Controller
{
    use MobileTrait;




public function show($id)
{
    $user = User::with('country', 'hobbies', 'albums', 'vip.defaultFrame', 'vip.defaultEntry', 'defaultFrame','defaultEntry','level', 'recievedContributions', 'sentContributions', 'fiveGifts', 'groupMember.group', 'RoomMember.room')->withCount('followings', 'followers', 'friends')->findOrFail($id);

    // Handle following & Friend FLAGS
    $authUser = auth()->user();
    $user->is_following = 0;
    $user->is_friend = 0;

    foreach ($authUser->followings as $following) {
        if ($following && $following->followingUser && $following->followingUser->id == $user->id) {
            $user->is_following = 1;
        }
    }

    foreach ($authUser->friends as $friend) {
        if ($friend && $friend->friendUser && $friend->friendUser->id == $user->id) {
            $user->is_friend = 1;
        }
    }
    $user->makeHidden(['vip_time']);

    return $this->data($user);
}


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            "provider_id" => ['numeric'],
            "provider_name" => ['string', 'in:facebook,google'],
            "name" => ['string', 'min:2', 'max:150'],
            "email" => ['email'],
            "profile_img" => ['mimes:jpeg,png,jpg,gif,max:2048'],
            "phone" => ['min:10', 'max:14','unique:users,phone,'.$user->id],
            'dob' => ['date', 'before:2005'],
            "gender" => ['string', 'in:male,female'],
            'about_me' => ['string', 'min:3', 'max:1000'],
            'country_id' => ['numeric', 'exists:countries:id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($request->hasFile('profile_img')){
            $profileImage = Storage::disk('public')->putFile('images/users/profile_images', new File($request->profile_img));

            $request->request->add(['profile_picture' => $profileImage]);
        }

        try{
            $user->update($request->all());

            return $this->successWithoutData('تم تحديث ملفك الشخصي بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function destroy($id)
    {
        //
    }

    public function addPhotoToAlbum(Request $request)
    {
        $user = auth()->user();

        $rules = [
            "album_img" => ['required', 'mimes:jpeg,png,jpg,gif,max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        /* Limit to 6 */
        if(!empty($user->albums)){
            if(count($user->albums) >= 6){
                return $this->error('عفوا، لقد تجاوزت الحد الأقصى من الصور', 403);
            }
        }

        $path = Storage::disk('public')->putFile('images/users/albums', new File($request->album_img));
        $request->request->add(['path' => $path]);

        if($user->albums != null){
            $counter = count($user->albums);
            $order = $counter + 1;
        }else{
            $order = 1;
        }

        try{
            $album = PhotoAlbum::create([
                'path' => $path,
                'order' => $order,
                'user_id' => $user->id,
            ]);

            return $this->success('تم إضافة الصورة إلى الألبوم بنجاح، أضف المزيد لمظهر أفضل!');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function deletePhotoFromAlbum($id){
        $photo = PhotoAlbum::findOrFail($id);

        if($photo->user_id != auth()->user()->id){
            return $this->error('متهزرش ياعم!', 403, 403);
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return $this->successWithoutData('تم حذف الصورة بنجاح',);
    }
    
    
    public function convertDiamondToGold(Request $request)
{
    
    $user = auth()->user();

    $rules = [
        'amount' => ['required', 'numeric', 'min:1'],
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $requestedAmount = $request->amount;

    // Check if the user has enough diamonds
    if ($user->gold_balance < $requestedAmount) {
        return response()->json(['success' => false, 'error' => 'Insufficient diamonds to make the transfer'], 400);
    }

    // Calculate the equivalent gold (one-sixth of the diamonds)
    $goldAmount = $requestedAmount / 6;

    try {
        // Update the user's balances
        $user->gold_balance -= $requestedAmount;
        $user->diamond_balance += $goldAmount;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Conversion successful',
            'data' => [
                'gold_balance' => $user->gold_balance,
                'diamond_balance' => $user->diamond_balance,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred, please try again later',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function addHobby(Request $request)
    {
        $user = auth()->user();

        $rules = [
            "body" => ['required', 'string', 'min:2', 'max:255'],
            "type" => ['required', 'in:music,movies,animals,sports'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        /* Limit each type to 3 */
        if($user->hobbies != null){
            $certainHobbies = Hobby::where('user_id', $user->id)->where('type', $request->type)->get();

            if($certainHobbies != null){
                if(count($certainHobbies) >= 3){
                    return $this->error('عفوا، لقد تجاوزت الحد الأقصى المسموح به في كل نوع من أنواع الهوايات', 403);
                }
            }
        }

        $request->request->add(['user_id' => $user->id]);
        try{
            $hobby = Hobby::create($request->all());

            return $this->successWithoutData('تم إضافة الهواية بنجاح، أضف المزيد الأن للحصول على مظهر أفضل');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function index(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->data($user);
    }

    public function editHobby(Request $request, $id)
    {
        $hobby = Hobby::findOrFail($id);
        $user = auth()->user();

        $rules = [
            "body" => ['required', 'string', 'min:2', 'max:255'],
            //"type" => ['required', 'in:music,movies,animals,sports'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($hobby->user_id != $user->id){
            return $this->error('خد 403', 403);
        }

        try{
            $hobby->update($request->all());

            return $this->success('تم تعديل الهواية بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function deleteHobby($id){
        $hobby = Hobby::findOrFail($id);

        if($hobby->user_id != auth()->user()->id){
            return $this->error('متهزرش ياعم!', 403, 403);
        }

        $hobby->delete();

        return $this->successWithoutData('تم حذف الهواية بنجاح');
    }



    public function findUserByID(Request $request)
    {
        $rules = [
            'uid' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        // $user = User::where('uid', $request->uid)->first();
        // $vip = VipUser::find($user->vip)->with('defaultFrame', 'defaultEntry')->first();
        $user = User::where('uid', $request->uid)->first();
        $vip = $user->vip ? VipUser::find($user->vip)->with('defaultFrame', 'defaultEntry')->first() : null;

        return response()->json(['user' =>$user, 'vip'=> $vip], 200);
    }

    // dd('ss');



}
