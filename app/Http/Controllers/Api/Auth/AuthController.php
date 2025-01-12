<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Store\SpecialUID;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use App\Models\Level;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use MobileTrait;

    public function login(Request $request)
    {
        $rules = [
            "phone" => ['required'],
            "password" => ['required'],
        ];

        $credentials = request(['phone', 'password']);

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }
        $token = JWTAuth::attempt([
            "phone" => $request->phone,
            "password" => $request->password
        ]);
        if (!$token = auth()->attempt($credentials)) {
            return $this->error('Invalid Phone or Password', 422);
        }
    if(!empty($token)){
        $user = User::where('phone', $credentials['phone'])->first();
    $today = Carbon::today();
    if($user->deactivated_until >= $today){
        if($user->deactivated_until === '1-1-3099'){
            return $this->error('عفوا، هذا الحساب محظور بشكل دائم.', 401);
        }
        return $this->error('عفوا، هذا الحساب محظور حتى تاريخ: '.$user->deactivated_until, 401);
    }
       return $this->data($token);
    }

    // return response()->json([
    //     "status" => false,
    //     "message" => "Invalid details"
    // ]);
        if (!$token = auth()->attempt($credentials)) {
            return $this->error('Invalid Phone or Password', 422);
        }
        $user = auth()->user();
        $user->access_token = $token;
        $user->token_expires_in = auth()->factory()->getTTL() * 1;

        /* Check if user is blocked */
        $today = Carbon::today();
        if($user->deactivated_until >= $today){
            if($user->deactivated_until === '1-1-3099'){
                return $this->error('عفوا، هذا الحساب محظور بشكل دائم.', 401);
            }
            return $this->error('عفوا، هذا الحساب محظور حتى تاريخ: '.$user->deactivated_until, 401);
        }

        return $this->data($token);
    }

    public function store(Request $request)
    {
        $rules = [
            "device_id" => ['required'],
            //"device_token" => ['required'],

            "provider_id" => [Rule::requiredIf($request->uid == null && $request->phone == null), 'numeric'],
            "provider_name" => [Rule::requiredIf($request->provider_id != null), 'string', 'in:facebook,google'],
            "name" => [Rule::requiredIf($request->provider_id != null && !User::where('provider_id', $request->provider_id)->where('provider_name', $request->provider_name)->exists()), 'min:3','max:20'],
            "email" => ['nullable', 'email'],
            "profile_image" => Rule::requiredIf($request->provider_id != null && !User::where('provider_id', $request->provider_id)->where('provider_name', $request->provider_name)->exists()),

            "uid" => Rule::requiredIf($request->provider_id == null && $request->phone == null),
            "password" => Rule::requiredIf($request->uid != null),

            "phone" => Rule::requiredIf($request->uid == null && $request->provider_id == null),

            "country_code" => ['required', 'min:2', 'max:2'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($request->provider_name !== null){

            try{
                $user = User::firstOrCreate(
                    ['provider_id' => $request->provider_id, 'provider_name' => $request->provider_name],

                    [
                        'provider_id' => $request->provider_id,
                        'provider_name' => $request->provider_name,
                        'name' => $request->name,
                        'email' => $request->email,
                        'profile_picture' => $request->profile_image,
                        'device_id' => $request->device_id,
                        'uid' => $this->generateUID(),
                        'phone' => $request->phone,
                        'country_code' => $request->country_code,
                        'level_id' => 1,
                        'password'=>Hash::make($request->password),
                    ],
                );


            }catch(QueryException $e){
                // return $e;
                return $this->error500();
            }
        }elseif($request->phone !== null){
            try{

                $user = User::firstOrCreate(
                    ['phone' => $request->phone, 'device_id' => $request->device_id],

                    [
                        'provider_id' => $request->provider_id,
                        'provider_name' => $request->provider_name,
                        'name' => $request->name,
                        'email' => $request->email,
                        'profile_picture' => $request->profile_image,
                        'device_id' => $request->device_id,
                        //'device_token' => $request->device_token,
                        'password'=> Hash::make($request->password),
                        'uid' => $this->generateUID(),
                        'phone' => $request->phone,
                        'country_code' => $request->country_code,
                        'level_id' => 1,
                    ],
                );
            }catch(QueryException $e){
                // return $e;
                return $this->error500();
            }
        }elseif($request->uid !== null){
            $userObj = User::where('uid', $request->uid);

            if(!$userObj->exists()){
                return $this->error('عضو غير مسجل', 403);
            }

            $userObj = $userObj->first();
            if(Hash::check($request->password, $userObj->password)){
                $user = $userObj;
            }else{
                return $this->error('كلمة المرور خاطئة', 403);
            }
        }

        /* Check if user is blocked */
        $today = Carbon::today();
        if($user->deactivated_until >= $today){
            if($user->deactivated_until === '1-1-3099'){
                return $this->error('عفوا، هذا الحساب محظور بشكل دائم.', 401);
            }
            return $this->error('عفوا، هذا الحساب محظور حتى تاريخ: '.$user->deactivated_until, 401);
        }


        $token = auth()->login($user);

        //Handle First Time login to continue info with phone Register
        $firstTime = false;
        $userCreatedAtParsed = Carbon::parse($user->created_at)->format('Y-m-d H:i');

        if($userCreatedAtParsed == now()->format('Y-m-d H:i')){
            $firstTime = true;
        }

        $data = ['token' => $token, 'first_time' => $firstTime];
        return $this->data($data, 'Hello Buddy :)');
    }

    public function me()
    {
        $user = auth()->user();

        // $user->followings_count = strval(count($user->followings));
        // $user->followers_count = strval(count($user->followers));
        // $user->friends_count = strval(count($user->friends));
        // $user->country = $user->country;
        // $user->level = $user->level;
        // $user->sentContributions = $user->sentContributionsAmount;
        // $user->recievedContributions = $user->recievedContributionsAmount;
        // $user->fiveGifts = $user->fiveGifts;
        // $user->default_entry = $user->defaultEntry;
        // $user->default_frame = $user->defaultFrame;
        // $user->groupMember = $user->groupMember()->with('group')->first();


            $user = User::with('country', 'hobbies', 'albums', 'vip.defaultFrame', 'vip.defaultEntry', 'defaultFrame','defaultEntry','level', 'recievedContributions', 'sentContributions', 'fiveGifts', 'groupMember.group', 'RoomMember.room')->withCount('followings', 'followers', 'friends')->findOrFail($user->id);
            
    
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
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout(true);

        return $this->successWithoutData('تم تسجيل خروجك بنجاح، سوف ننتظر عودتك');
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'old_password' => [Rule::requiredIf($user->password != null)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        /* manual validation */
        if($user->password != null){
            if(!Hash::check($request->old_password, $user->password)){
                return $this->error('كلمة المرور خاطئة', 403);
            }
        }

        try{
            $user->password = Hash::make($request->password);
            $user->save();

            return $this->successWithoutData('تم تغيير كلمة المرور بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    protected function generateUID()
    {
        $uId = rand(100000000, 999999999);

        if($this->uIDExists($uId)){
            $this->generateUID();
        }

        return strval($uId);
    }

    protected function uIDExists($uId)
    {
        if(SpecialUID::where('body', $uId)->exists()){
            return true;
        }

        if(User::where('uid', $uId)->exists()){
            return true;
        }

        return false;
    }

}
