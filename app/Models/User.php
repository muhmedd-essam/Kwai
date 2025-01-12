<?php

namespace App\Models;

use App\Models\HostingAgency\HostingAgencyMemberBd;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;
use App\Models\Store\Decoration;
use App\Models\PhotoAlbum;
use App\Models\Hobby;
use App\Models\Following;
use App\Models\Friend;
use Illuminate\Support\Str;
use App\Models\Country;
use App\Models\Gift;
use App\Models\Rooms\RoomMember;
use App\Models\Rooms\RoomChair;
use App\Models\Level;
use App\Models\Rooms\RoomContribution;
use App\Models\Chat\ChatBlock;
use App\Models\Agents\ChargeAgent;
use App\Models\Gift\GiftReceipt;
use App\Models\GroupMember;
use App\Models\HostingAgency\HostingAgency;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\VideoGifts\VideoGift;
use App\Models\Reels\Reel;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'users';

    protected $fillable = [
        'device_id',
        'device_token',
        'provider_id',
        'provider_name',
        'name',
        'email',
        'profile_picture',
        'uid',
        'phone',
        'password',
        'dob',
        'gender',
        'about_me',
        'country_code',
        'level_id',
        'vip',
        'vip_time'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'diamond_balance' => 'integer',
        'gold_balance' => 'integer',
        'age' => 'integer',
        'default_frame_id' => 'integer',
        'default_entry_id' => 'integer',
        // 'room_member' => 'array', // تأكد من تحويل RoomMember إلى مصفوفة

    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

    //  public function index(Request $request){
    //     $user = auth()->user();
    //     if (!$user) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }
    //     return $this->data($user);
    // }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setLevelIdAttribute($value)
    {
        $this->attributes['level_id'] = $value;
        if($value == 99){
            $this->attributes['next_level_id'] = $value;
        }else{
            $this->attributes['next_level_id'] = $value + 1;
        }
    }

    public function getProfilePictureAttribute($value){
        if(Str::contains($value, 'http')){
            return $value;
        }

        if($value == null){
            return null;
        }

        return asset('/storage/' . $value);
    }

    public function getAgeAttribute($value)
    {
        $dob = new Carbon($this->dob);
        $now = Carbon::now();

        if($dob->diffInYears($now) == 0){
            $value = null;
        }else{
            $value = $dob->diffInYears($now);
        }

        return $value;
    }

    public function decorations()
    {
        return $this->belongsToMany(Decoration::class);
    }
    public function country()
    {
        return $this->hasOne(Country::class, 'code', 'country_code');
    }

    public function albums()
    {
        return $this->hasMany(PhotoAlbum::class);
    }

    public function hobbies()
    {
        return $this->hasMany(Hobby::class);
    }

    public function followings()
    {
        return $this->hasMany(Following::class, 'follower_id');
    }

    public function followers()
    {
        return $this->hasMany(Following::class, 'following_id');
    }

    public function friends()
    {
        return $this->hasMany(Friend::class, 'user_id');
    }

    public function roomMember()
    {
        return $this->hasOne(RoomMember::class , 'user_id')->withDefault([]);
    }

    public function defaultFrame()
    {
        return $this->belongsTo(Decoration::class, 'default_frame_id');
    }

    public function defaultEntry()
    {
        return $this->belongsTo(Decoration::class, 'default_entry_id');
    }

    public function gifts()
    {
        return $this->belongsToMany(Gift::class)->withPivot('quantity');
    }

    public function fiveGifts()
    {
        return $this->belongsToMany(Gift::class)->take(5);
    }

    public function videoGifts()
    {
        return $this->belongsToMany(VideoGift::class)->withPivot('quantity');
    }

    public function currentLevel()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'next_level_id');
    }

    public function onChair()
    {
        return $this->hasOne(RoomChair::class)->with('user.level', 'user.getNextLevel');
    }

    public function recievedContributions()
    {
        $carbonMinus7Days = Carbon::now()->subDays(7);
        return $this->hasMany(RoomContribution::class, 'receiver_id')->whereDate('created_at', '>=', $carbonMinus7Days);
    }

    public function getRecievedContributionsAmountAttribute()
    {
        return $this->recievedContributions()->sum('amount');
    }

    public function sentContributions()
    {
        $carbonMinus7Days = Carbon::now()->subDays(7);
        return $this->hasMany(RoomContribution::class, 'sender_id')->whereDate('created_at', '>=', $carbonMinus7Days);
    }

    public function getSentContributionsAmountAttribute()
    {
        return $this->sentContributions()->sum('amount');
    }

    public function blocks()
    {
        return $this->hasMany(ChatBlock::class, 'blocker_id');
    }

    public function chargeAgent()
    {
        return $this->hasOne(ChargeAgent::class, 'user_id');
    }

    public function groupMember()
    {
        return $this->hasOne(GroupMember::class, 'user_id');
    }

    public function hostingAgencyOwner()
    {
        return $this->hasOne(HostingAgency::class, 'owner_id');
    }

    public function hostingAgencyMember()
    {
        return $this->hasOne(HostingAgencyMember::class, 'user_id');
    }

    public function hostingAgencyMemberBd()
    {
        return $this->hasOne(HostingAgencyMemberBd::class, 'user_id');
    }

    public function hostingAgencyBd()
    {
        return $this->hasOne(HostingAgency::class, 'bd');
    }

    public function reels()
    {
        return $this->hasMany(Reel::class);
    }

    public function vip(){
        return $this->belongsTo(VipUser::class, 'vip', 'id');
    }

    public function giftReceipts()
    {
        return $this->hasMany(GiftReceipt::class);
    }


    // public function toArray()
    //     {
    //         $array = parent::toArray();

    //         // تحويل خصائص null إلى 0
    //         return array_map(function ($value) {
    //             return is_null($value) ? [] : $value;
    //         }, $array);
    //     }

        public function profilePicture()
{
    $array = parent::toArray();

    // التحقق من profile_picture وجعلها صورة افتراضية بناءً على gender إذا كانت null
    if (is_null($array['profile_picture'])) {
        if (isset($array['gender']) && $array['gender'] === 'male') {
            $array['profile_picture'] = 'https://wolfchat.online/male.jpg';
        } elseif (isset($array['gender']) && $array['gender'] === 'female') {
            $array['profile_picture'] = 'https://wolfchat.online/female.jpg';
        }
    }

    return $array; // إرجاع البيانات كما هي دون أي تعديل على القيم الأخرى
}

}
