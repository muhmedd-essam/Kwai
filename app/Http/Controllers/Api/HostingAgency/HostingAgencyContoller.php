<?php

namespace App\Http\Controllers\Api\HostingAgency;

use App\Http\Controllers\Controller;
use App\Models\Rooms\RoomContribution;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\HostingAgency\HostingAgency;
use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\HostingAgency\MemberRequest;
use App\Models\HostingAgency\HostingAgencyTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class HostingAgencyContoller extends Controller
{
    use MobileTrait;

    public function myAgency()
    {
        $user = auth()->user();

        // Carbon::setLocale('ar_SA');
        // $today = Carbon::today('Asia/Riyadh');

        Carbon::setLocale('ar_EG');
        $today = Carbon::today('Africa/Cairo');


        // if ($today->day == 1) {

        //     $cacheKey = 'update_on_first_day_' . $today->format('Y_m_d');

        //     // $agencyMembership = HostingAgencyMember::where('user_id', $user->id)->first();
        //     if (!Cache::has($cacheKey)) {
        //         $agencyMembers = HostingAgencyMember::all();

        //         foreach ($agencyMembers as $agencyMember) {
        //             $performance = HostingAgencyDiamondPerformance::where('agency_member_id', $agencyMember->user_id)->sum('amount');
        //             $performanceNext = HostingAgencyTarget::find($agencyMember->current_target_id);
        //             $resultPerfomance = $performance - $performanceNext->diamonds_required;
        //             if($resultPerfomance > 0){
        //                 HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMember->id, 'supporter_id' => $agencyMember->user_id, 'hosting_agency_id' => $agencyMember->agency->id, 'amount' => $resultPerfomance, 'created_at' => now(), 'updated_at' => now()]);
        //             }

        //             $agencyMember->current_target_id = 1;
        //             $agencyMember->save();
        //         }
        //     // delete data from DiamondPerformance and RoomContribution
        //         HostingAgencyDiamondPerformance::truncate();
        //         RoomContribution::truncate();
        //     // make support send and recieve = 0
        //     // supported sender and reciever Bonus
        //         $allUsers = User::all();
        //         foreach ($allUsers as $user) {

        //             if ($user->supported_send >= 100000){
        //                 $user->diamond_balance= $user->supported_send * 0.1;
        //                 $user->save();
        //             }
        //             $targetMember=HostingAgencyMember::where('user_id', $user->id)->first();
        //             if($targetMember->current_target_id == 5){
        //                 if ($user->supported_recieve >= 90000 && $user->supported_recieve < 120000) {
        //                     $user->vip = 1;
        //                     $user->save();
        //                 } elseif ($user->supported_recieve >= 120000) {
        //                     $user->vip = 2;
        //                     $user->save();
        //                 }
        //             }
        //             $user->supported_send=0;
        //             $user->supported_recieve= 0;
        //             $user->save();
        //         }
        //         Cache::put($cacheKey, true, $today->endOfDay());
        //     }
        // }


        if($user->is_hosting_agency_owner == 1 && $user->is_hosting_agent == 1){
            

            $members = HostingAgencyMember::where('hosting_agency_id', $user->id)->with('target')->get();

            $salary = 0;
            foreach($members as $member){
                $salary += $member->target->owner_salary;
                // $salary += $member->videoTarget->owner_salary;
            }
            $agency = HostingAgency::where('owner_id', $user->id)->withCount('members')->withSum('diamondPerformance', 'amount')->withSum('hoursPerformance','duration')->first();
            $agency->salary = $salary;
            //owner if he is a member in his hosting
            $salaryMember = HostingAgencyMember::where('user_id', $user->id)->with('target')->first();
            $salaryAmount = $salaryMember->target->salary;
            $agency->salary += $salaryAmount;
            if ($salaryMember->day_salary > 0){
                $agency->salary= $salaryMember->day_salary .' Day Left for salary' ;
            }
            return $this->data($agency);
        }elseif($user->is_hosting_agent == 1 && $user->is_hosting_agency_owner == 0){
                        dd('ss');

            $membership = HostingAgencyMember::where('user_id', $user->id)->withSum(['diamondPerformance' => function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            }], 'amount')->withSum('hoursPerformance','duration')->with('target', 'agency.owner')->first();

            $salary = HostingAgencyMember::where('user_id', $user->id)->with('target')->first();
            $salaryAmount = $salary->target->salary;
            $membership->salary = $salaryAmount;

            $nextTarget = HostingAgencyTarget::where('target_no',$membership->target->target_no+1)->first();
            if ($salary->day_salary > 0){
                $membership->salary= $membership->day_salary .' Day Left for salary' ;
            }

            return $this->data(['membership' => $membership, 'next_target' => $nextTarget]);
        }else{
            return $this->error('انت لست صاحب وكالة أو مضيف في وكالة', 403);
        }
    }

    public function getOwnerSalary($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('target')->get();

        $salary = 0;
        foreach($members as $member){
            $salary+= $member->target->owner_salary;
            $salary+= $member->videoTarget->owner_salary;
        }

        return $this->data(['salary' => $salary]);
    }

    public function members($id)
    {
        $agency = HostingAgency::findOrFail($id);

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user')->orderBy('id', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function membersDiamondsPerformance($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user')->withSum('diamondPerformance', 'amount')->orderBy('diamond_performance_sum_amount', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function membersVideoDiamondsPerformance($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user')->withSum('videoDiamondPerformance', 'amount')->orderBy('video_diamond_performance_sum_amount', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function membersHoursPerformance($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user')->withSum('hoursPerformance', 'duration')->orderBy('hours_performance_sum_duration', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function membersVideoHoursPerformance($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user')->withSum('videoHoursPerformance', 'duration')->orderBy('video_hours_performance_sum_duration', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function membersTargets($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user', 'target')->withSum('hoursPerformance', 'duration')->withSum('diamondPerformance', 'amount')->orderBy('hours_performance_sum_duration', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function membersVideoTargets($id)
    {
        $agency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($agency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $members = HostingAgencyMember::where('hosting_agency_id', $id)->with('user', 'videoTarget')->withSum('videoHoursPerformance', 'duration')->withSum('videoDiamondPerformance', 'amount')->orderBy('video_hours_performance_sum_duration', 'DESC')->paginate(100);

        return $this->dataPaginated($members);
    }

    public function update(Request $request, $id)
    {
        $hostingAgency = HostingAgency::findOrFail($id);
        $user = auth()->user();

        if($hostingAgency->owner_id != $user->id){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        $rules = [
            'name' => ['string', 'min:2', 'max:70', 'unique:hosting_agencies,name,'.$hostingAgency->id],
            'description' => ['string', 'min:5', 'max:1500'],
            'cover_image' => ['mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        //Upload
        if($request->hasFile('cover_image')){
            $cover = Storage::disk('public')->putFile('images/hosting-agencies/covers', new File($request->cover_image));

            $request->request->add(['cover' => $cover]);
        }

        try{
            $update = $request->only('name', 'description', 'cover');

            foreach($update as $key => $value) {
                $hostingAgency->$key = $value;
            }
            $hostingAgency->save();

            return $this->successWithoutData('تم تحديث بيانات الوكالة');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function kick($id) //Member ID
    {
        $member = HostingAgencyMember::findOrFail($id);

        if($member->agency->owner_id != auth()->id()){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        try{
            $userMember = $member->user;
            $userMember->is_hosting_agent = 0;
            $userMember->save();

            $member->delete();

            return $this->successWithoutData('تم طرد المستخدم من الوكالة بنجاح');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function getJoinRequests($id)
    {
        $hostingAgency = HostingAgency::findOrFail($id);

        $joinRequests = MemberRequest::where('hosting_agency_id', $id)->with('user')->orderBy('id', 'DESC')->paginate(100);

        return $this->dataPaginated($joinRequests);
    }

    public function acceptOrDeclineMember(Request $request ,$id)
    {
        $joinRequest = MemberRequest::findOrFail($id);

        $rules = [
            'status' => ['numeric', 'in:0,1'], // 0 => decline, 1 => accept
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($joinRequest->agency->owner_id != auth()->id()){
            return $this->error('هذه الوكالة غير خاصة بك', 403);
        }

        if($joinRequest->user->is_hosting_agent == 1){
            $joinRequest->delete();
            return $this->error('المستخدم بالفعل انضم إلى وكالة اخرى', 403);
        }

        try{
            if($request->status == 1){

                $newMember = HostingAgencyMember::create([
                    'hosting_agency_id' => $joinRequest->agency->id,
                    'user_id' => $joinRequest->user->id,
                    'current_target_id' => 1,
                    'current_target_video_id' => 1,
                ]);

                $user = $joinRequest->user;
                $user->vip = 3;
                $user->is_hosting_agent = 1;
                $user->save();

                $joinRequest->delete();

                return $this->successWithoutData('تم قبول الطلب بنجاح');
            }
            $joinRequest->delete();

            return $this->successWithoutData('تم رفض الطلب');

        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:70', 'unique:hosting_agencies,name'],
            'description' => ['required', 'string', 'min:5', 'max:1500'],
            'cover_image' => ['required', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $user = auth()->user();

        if($user->is_hosting_agency_owner == 1 || $user->is_hosting_agent == 1)
        {
            return $this->error('أنت إما مضيف في وكالة أو صاحب وكالة اخرى، يجب عليك مغادرة وكالتك أولا', 403);
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/hosting-agencies/covers', new File($request->cover_image));

        $aId = $this->generateAId();

        try{
            $hostingAgency = HostingAgency::insert(['name' => $request->name, 'description' => $request->description, 'cover' => $cover, 'aid' => $aId, 'owner_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);


            $agency = HostingAgency::where('owner_id', $user->id)->first();
            // register admin as member in hosting member
            

                $newMember = HostingAgencyMember::create([
                    'hosting_agency_id' => $agency->id,
                    'user_id' => $user->id,
                    'current_target_id' => 1,
                    'current_target_video_id' => 1,
                    // 'bd'=>$request->bd ,
                ]);
                
                // dd('ss');

    
    $user->is_hosting_agency_owner = 1;
    
    $user->is_hosting_agent = 1;
    
    $user->save();
    // dd($user);

            return $this->successWithoutData('تم إنشاء الوكالة بنجاح، نتمنى لك التوفيق');
    //         return response()->json([
    //   'success' => true,
    //   'message' => 'تم إنشاء الوكالة بنجاح، نتمنى لك التوفيق',
    // ]);
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    protected function generateAId()
    {
        $aId = rand(1000, 9999);

        if($this->aIdExists($aId)){
            $this->generateAId();
        }

        return $aId;
    }

    protected function aIdExists($aId)
    {
        if(HostingAgency::where('aid', $aId)->exists()){
            return true;
        }

        return false;
    }

}
